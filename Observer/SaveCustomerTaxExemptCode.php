<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Observer;

use ATF\Zamp\Model\Service\TaxExemptCodeResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class SaveCustomerTaxExemptCode implements ObserverInterface
{
    /**
     * @param TaxExemptCodeResolver $taxExemptCodeResolver
     */
    public function __construct(
        private readonly TaxExemptCodeResolver $taxExemptCodeResolver
    ) {
    }

    /**
     * Set sales_order zamp_customer_tax_exempt_code to the effective resolver value (profile or group).
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /* @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof Order) {
            return;
        }

        $customerId = $order->getCustomerId() ? (int)$order->getCustomerId() : null;
        $code = $this->taxExemptCodeResolver->execute($customerId);
        if ($code !== null) {
            $order->setZampCustomerTaxExemptCode($code);
            return;
        }

        if ($orderCustomerTaxExemptCode = $order->getData('customer_tax_exempt_code')) {
            $order->setZampCustomerTaxExemptCode($orderCustomerTaxExemptCode);
        }
    }
}
