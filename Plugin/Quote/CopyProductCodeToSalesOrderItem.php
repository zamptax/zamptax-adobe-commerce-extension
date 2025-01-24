<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Quote;

use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Sales\Model\Order\Item;
use ATF\Zamp\Model\Configurations;

class CopyProductCodeToSalesOrderItem
{
    /**
     * @var Configurations
     */
    private $config;

    /**
     * @param Configurations $config
     */
    public function __construct(
        Configurations $config
    ) {
        $this->config = $config;
    }

    /**
     * Set sales_order_items tax_provider_tax_code column value
     *
     * @param ToOrderItem $subject
     * @param \Closure $proceed
     * @param AbstractItem $item
     * @param array $additional
     * @return Item
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundConvert(
        ToOrderItem $subject,
        \Closure $proceed,
        AbstractItem $item,
        $additional = []
    ) {
        $productTaxCode = $item->getProduct()->getTaxProviderTaxCode();
        if (empty($productTaxCode)) {
            $productTaxCode = $this->config->getDefaultProductTaxProviderTaxCode();
        }

        /** @var $orderItem Item */
        $orderItem = $proceed($item, $additional);
        $orderItem->setTaxProviderTaxCode($productTaxCode);
        return $orderItem;
    }
}
