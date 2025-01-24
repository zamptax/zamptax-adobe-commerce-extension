<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Observer;

use ATF\Zamp\Services\Quote as QuoteService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class SaveOrderBeforeSalesModelQuoteObserver implements ObserverInterface
{
    /**
     * Copy quote to sales order
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer): static
    {
        /* @var Order $order */
        $order = $observer->getEvent()->getData('order');

        /* @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        $order->setData(QuoteService::IS_ZAMP_CALCULATED, $quote->getData(QuoteService::IS_ZAMP_CALCULATED));

        return $this;
    }
}
