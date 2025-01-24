<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddUpdateTaxBlock implements ObserverInterface
{
    public const AFFECTED_LAYOUTS = [
        'sales_email_order_creditmemo_items',
        'sales_email_order_invoice_items',
        'sales_email_order_items',
        'sales_guest_creditmemo',
        'sales_guest_invoice',
        'sales_guest_print',
        'sales_guest_printcreditmemo',
        'sales_guest_printinvoice',
        'sales_guest_view',
        'sales_order_creditmemo',
        'sales_order_creditmemo_new',
        'sales_order_creditmemo_updateqty',
        'sales_order_creditmemo_view',
        'sales_order_invoice',
        'sales_order_invoice_new',
        'sales_order_invoice_updateqty',
        'sales_order_invoice_view',
        'sales_order_print',
        'sales_order_printcreditmemo',
        'sales_order_printinvoice',
        'sales_order_view'
    ];

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $layout = $observer->getData('layout');
        $currentHandles = $layout->getUpdate()->getHandles();

        if (array_intersect(self::AFFECTED_LAYOUTS, $currentHandles)) {
            $layout->getUpdate()->addHandle('update_tax_block');
        }

        return $this;
    }
}
