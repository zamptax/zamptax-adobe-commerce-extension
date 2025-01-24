<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Observer;

use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\Refund;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;

class SendRefundToZamp implements ObserverInterface
{
    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var Refund
     */
    protected $refund;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Configurations $config
     * @param Refund $refund
     * @param Logger $logger
     */
    public function __construct(
        Configurations $config,
        Refund $refund,
        Logger $logger
    ) {
        $this->config = $config;
        $this->refund = $refund;
        $this->logger = $logger;
    }

    /**
     * Execute the observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Creditmemo $creditmemo */
        $creditMemo = $observer->getEvent()->getCreditmemo();

        $doZamp = $this->config->isModuleEnabled()
            && $this->config->isSendTransactionsEnabled();

        if ($doZamp) {
            try {
                $this->refund->execute($creditMemo);
            } catch (\Exception $e) {
                $this->logger->error(__('Error sending refund to Zamp: %1', $e->getMessage()));
            }
        }
    }
}
