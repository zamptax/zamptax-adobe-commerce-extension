<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class SaveCustomerTaxExemptCode implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * Set sales_order zamp_customer_tax_exempt_code column value
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($this->customerSession->isLoggedIn() && !empty($this->customerSession->getCustomer()->getTaxExemptCode())) {
            $order->setZampCustomerTaxExemptCode($this->customerSession->getCustomer()->getTaxExemptCode());
        } elseif ($orderCustomerTaxExemptCode = $order->getData('customer_tax_exempt_code')) {
            $order->setZampCustomerTaxExemptCode($orderCustomerTaxExemptCode);
        }
    }
}
