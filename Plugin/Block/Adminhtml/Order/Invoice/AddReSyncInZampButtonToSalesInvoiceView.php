<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Block\Adminhtml\Order\Invoice;

use ATF\Zamp\Model\Configurations;
use Magento\Sales\Block\Adminhtml\Order\Invoice\View;
use Magento\Backend\Model\UrlInterface;

class AddReSyncInZampButtonToSalesInvoiceView
{
    /**
     * @var Configurations
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param Configurations $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Configurations $config,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Adds Re-sync in zamp button to sales invoice view
     *
     * @param View $view
     * @return void
     */
    public function beforeSetLayout(View $view)
    {
        $doZamp = $this->config->isModuleEnabled()
            && $this->config->isSendTransactionsEnabled();

        $invoice = $view->getInvoice();
        $invoiceId = $invoice->getId();
        $zampTransactionId = $invoice->getZampTransactionId();

        if ($invoiceId && $doZamp && !$zampTransactionId) {
            $url = $this->urlBuilder->getUrl(
                'zamp/transaction/resyncInvoice/',
                ['invoice_id' => $invoiceId]
            );

            $view->addButton(
                're_sync_in_zamp',
                [
                    'label' => __('Re-sync in Zamp'),
                    'onclick' => 'setLocation(\'' . $url . '\')',
                ],
                0,
                5
            );
        }
    }
}
