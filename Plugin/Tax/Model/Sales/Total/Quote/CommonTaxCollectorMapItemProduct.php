<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Tax\Model\Sales\Total\Quote;

use ATF\Zamp\Model\Extend\QuoteDetailsItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

/**
 * Plugin for CommonTaxCollector to apply product data
 */
class CommonTaxCollectorMapItemProduct
{
    /**
     * @var QuoteDetailsItem
     */
    private QuoteDetailsItem $extendQuoteDetailsItem;

    /**
     * @param QuoteDetailsItem $extendQuoteDetailsItem
     */
    public function __construct(
        QuoteDetailsItem $extendQuoteDetailsItem
    ) {
        $this->extendQuoteDetailsItem = $extendQuoteDetailsItem;
    }

    /**
     * After Map Item
     *
     * @param CommonTaxCollector $subject
     * @param QuoteDetailsItemInterface $result
     * @param QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param AbstractItem $item
     * @return QuoteDetailsItemInterface
     */
    public function afterMapItem(
        CommonTaxCollector               $subject,
        QuoteDetailsItemInterface        $result,
        QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        AbstractItem                     $item
    ): QuoteDetailsItemInterface {
        return $this->extendQuoteDetailsItem->execute($result, $item->getProduct(), $item);
    }
}
