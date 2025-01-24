<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Transaction;

class PayloadItems
{
    /**
     * Process Items for Zamp Line Item
     *
     * @param array $items
     * @return array
     */
    public function execute(array $items): array
    {
        $children = [];
        $zampItems = [];
        foreach ($items as $item) {
            if ($item->getPrice() && $item->getRowTotal()) {
                $zampItems[$item->getSku() . '-' . $item['order_item_id']] = $item;
            } else {
                $children[$item->getSku()] = $item->getProductId();
            }
        }

        /* fix product id for configurable items. */
        if (count($items) !== count($zampItems)) {
            foreach ($zampItems as $item) {
                $sku = $item->getSku();
                if (isset($children[$sku])) {
                    $item->setProductId($children[$sku]);
                }
            }
        }

        return $zampItems;
    }
}
