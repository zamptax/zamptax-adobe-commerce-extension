<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Preference\Model\Order\Pdf;

use ATF\Zamp\Preference\Model\Order\Pdf\DefaultPdf;
use ATF\Zamp\ViewModel\TaxViewModel;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Payment\Helper\Data;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Pdf\Config;
use Magento\Sales\Model\Order\Pdf\ItemsFactory;
use Magento\Sales\Model\Order\Pdf\Total\Factory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Pdf;
use Zend_Pdf_Color_GrayScale;
use Zend_Pdf_Color_Rgb;
use Zend_Pdf_Exception;
use Zend_Pdf_Page;
use Zend_Pdf_Style;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class Invoice extends DefaultPdf
{
    /**
     * @var TaxViewModel
     */
    private $taxViewModel;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Data $paymentData
     * @param StringUtils $string
     * @param ScopeConfigInterface $scopeConfig
     * @param Filesystem $filesystem
     * @param Config $pdfConfig
     * @param Factory $pdfTotalFactory
     * @param ItemsFactory $pdfItemsFactory
     * @param TimezoneInterface $localeDate
     * @param StateInterface $inlineTranslation
     * @param Renderer $addressRenderer
     * @param StoreManagerInterface $storeManager
     * @param Emulation $appEmulation
     * @param TaxViewModel $taxViewModel
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Data $paymentData,
        StringUtils $string,
        ScopeConfigInterface $scopeConfig,
        Filesystem $filesystem,
        Config $pdfConfig,
        Factory $pdfTotalFactory,
        ItemsFactory $pdfItemsFactory,
        TimezoneInterface $localeDate,
        StateInterface $inlineTranslation,
        Renderer $addressRenderer,
        StoreManagerInterface $storeManager,
        Emulation $appEmulation,
        TaxViewModel $taxViewModel,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $appEmulation,
            $taxViewModel,
            $logger,
            $data
        );
        $this->appEmulation = $appEmulation;
        $this->taxViewModel = $taxViewModel;
        $this->logger = $logger;
    }

    /**
     * Draw header for item table
     *
     * @param Zend_Pdf_Page $page
     * @param mixed $invoice
     * @return void
     * @throws LocalizedException
     */
    protected function _drawHeader(Zend_Pdf_Page $page, mixed $invoice = null): void
    {
        $taxLabel = $this->getOrderAndUpdatedTaxLabel($invoice);

        $this->_setFontRegular($page, 10);
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 15);
        $this->y -= 10;
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));

        $lines[0][] = ['text' => __('Products'), 'feed' => 35];
        $lines[0][] = ['text' => __('SKU'), 'feed' => 290, 'align' => 'right'];
        $lines[0][] = ['text' => __('Qty'), 'feed' => 435, 'align' => 'right'];
        $lines[0][] = ['text' => __('Price'), 'feed' => 360, 'align' => 'right'];
        $lines[0][] = ['text' => __($taxLabel), 'feed' => 495, 'align' => 'right'];
        $lines[0][] = ['text' => __('Subtotal'), 'feed' => 565, 'align' => 'right'];
        $lineBlock = ['lines' => $lines, 'height' => 5];

        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    /**
     * Return PDF document
     *
     * @param array|Collection $invoices
     * @return Zend_Pdf
     * @throws Zend_Pdf_Exception
     * @throws LocalizedException
     */
    public function getPdf($invoices = []): Zend_Pdf
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                $this->appEmulation->startEnvironmentEmulation(
                    $invoice->getStoreId(),
                    Area::AREA_FRONTEND,
                    true
                );
                $this->_storeManager->setCurrentStore($invoice->getStoreId());
            }
            $page = $this->newPage([], $invoice);
            $order = $invoice->getOrder();

            $this->insertLogo($page, $invoice->getStore());
            $this->insertAddress($page, $invoice->getStore());
            $this->insertOrder(
                $page,
                $order,
                $this->_scopeConfig->isSetFlag(
                    self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                )
            );
            $this->insertDocumentNumber($page, __('Invoice # ') . $invoice->getIncrementId());
            $this->_drawHeader($page, $invoice);
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            $this->insertTotals($page, $invoice);
            if ($invoice->getStoreId()) {
                $this->appEmulation->stopEnvironmentEmulation();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }

    /**
     * Create new page and assign to PDF object
     *
     * @param array $settings
     * @param mixed|null $invoice
     * @return Zend_Pdf_Page
     * @throws LocalizedException
     */
    public function newPage(array $settings = [], mixed $invoice = null): Zend_Pdf_Page
    {
        $page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;
        if (!empty($settings['table_header'])) {
            $this->_drawHeader($page, $invoice);
        }
        return $page;
    }
}
