<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Block\Adminhtml\Order\Creditmemo;

use ATF\Zamp\Model\Configurations;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\View;
use Magento\Backend\Model\UrlInterface;

class AddReSyncInZampButtonToSalesCreditMemoView
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
     * Adds Re-sync in zamp button to sales credit memo view
     *
     * @param View $view
     * @return void
     */
    public function beforeSetLayout(View $view)
    {
        $doZamp = $this->config->isModuleEnabled()
            && $this->config->isSendTransactionsEnabled();

        $creditMemo = $view->getCreditmemo();
        $creditMemoId = $creditMemo->getId();
        $zampTransactionId = $creditMemo->getZampTransactionId();

        if ($creditMemoId && $doZamp && !$zampTransactionId) {
            $url = $this->urlBuilder->getUrl(
                'zamp/transaction/resyncCreditMemo/',
                ['creditmemo_id' => $creditMemoId]
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
