<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\Transact;
use ATF\Zamp\Logger\Logger;

class SendTransactionToZamp implements ObserverInterface
{
    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var Transact
     */
    protected $transact;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Configurations $config
     * @param Transact $transact
     * @param Logger $logger
     */
    public function __construct(
        Configurations $config,
        Transact $transact,
        Logger $logger
    ) {
        $this->config = $config;
        $this->transact = $transact;
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
        /** @var InvoiceInterface $invoice */
        $invoice = $observer->getEvent()->getInvoice();

        $doZamp = $this->config->isModuleEnabled()
            && $this->config->isSendTransactionsEnabled();

        if ($doZamp) {
            try {
                $this->transact->execute($invoice);
            } catch (\Exception $e) {
                $this->logger->error(__('Error sending transaction to Zamp: %1', $e->getMessage()));
            }
        }
    }
}
