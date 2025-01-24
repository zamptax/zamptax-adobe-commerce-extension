<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Preference\Model\Order\Pdf;

use ATF\Zamp\ViewModel\TaxViewModel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Payment\Helper\Data;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Pdf\Config;
use Magento\Sales\Model\Order\Pdf\Invoice as MagentoInvoice;
use Magento\Sales\Model\Order\Pdf\ItemsFactory;
use Magento\Sales\Model\Order\Pdf\Total\Factory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Pdf_Page;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class DefaultPdf extends MagentoInvoice
{
    /**
     * @var TaxViewModel
     */
    private $taxViewModel;

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
            $data
        );
        $this->taxViewModel = $taxViewModel;
        $this->logger = $logger;
    }

    /**
     * Get the order of invoice and update the tax label
     *
     * @param mixed|null $invoiceOrCredit
     * @return string
     */
    public function getOrderAndUpdatedTaxLabel(mixed $invoiceOrCredit = null): string
    {
        $taxLabel = 'Tax';

        if ($invoiceOrCredit) {
            /** @var Order $order */
            $order = $invoiceOrCredit->getOrder();
            $taxLabel = $this->taxViewModel->getTaxLabel($order, $taxLabel);
        }

        return $taxLabel;
    }

    /**
     * Insert totals to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param AbstractModel $source
     * @return Zend_Pdf_Page
     * @throws LocalizedException
     */
    protected function insertTotals($page, $source): Zend_Pdf_Page
    {
        $order = $source->getOrder();
        $totals = $this->_getTotalsList();
        $lineBlock = ['lines' => [], 'height' => 15];
        foreach ($totals as $total) {
            $total->setOrder($order)->setSource($source);

            if ($total->canDisplay()) {
                $total->setFontSize(10);
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    $defaultLabel = $totalData['label'];
                    $taxLabel = 'Tax:';

                    if ($totalData['label'] === $taxLabel) {
                        $defaultLabel = $this->taxViewModel->getTaxLabel($order, $taxLabel);
                    }

                    $lineBlock['lines'][] = [
                        [
                            'text' => $defaultLabel,
                            'feed' => 475,
                            'align' => 'right',
                            'font_size' => $totalData['font_size'],
                            'font' => 'bold',
                        ],
                        [
                            'text' => $totalData['amount'],
                            'feed' => 565,
                            'align' => 'right',
                            'font_size' => $totalData['font_size'],
                            'font' => 'bold'
                        ],
                    ];
                }
            }
        }

        $this->y -= 20;
        return $this->drawLineBlocks($page, [$lineBlock]);
    }
}
