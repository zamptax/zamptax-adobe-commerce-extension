<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Extend;

use ATF\Zamp\Model\Configurations;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class QuoteDetailsItem
{
    /**
     * @var Configurations
     */
    private Configurations $configurations;

    /**
     * @param Configurations $configurations
     */
    public function __construct(
        Configurations $configurations
    ) {
        $this->configurations = $configurations;
    }

    /**
     * Extend Interface Data
     *
     * @param QuoteDetailsItemInterface $quoteDetailsItem
     * @param Product $product
     * @param AbstractItem|null $item
     * @return QuoteDetailsItemInterface
     */
    public function execute(
        QuoteDetailsItemInterface $quoteDetailsItem,
        Product $product,
        AbstractItem $item = null
    ): QuoteDetailsItemInterface {
        if ($extensionAttributes = $quoteDetailsItem->getExtensionAttributes()) {
            $product = $item ? $item->getProduct() : $product;

            $productId = $product->getId();

            if ($product->getTypeId() === Configurable::TYPE_CODE && $item->getChildren()) {
                $children = $item->getChildren();
                $child = reset($children);
                $child = $child->getProduct();
                $productId = $product->getId() . '-' .  $child->getId();
            } elseif ($product->getTypeId() !== Grouped::TYPE_CODE
                && $product->getTypeId() !== BundleType::TYPE_CODE
                && $item->getParentItem()) {
                $productId = $item->getParentItem()->getProduct()->getId() . '-' .  $product->getId();
            }

            $extensionAttributes->setProductId($productId);
            $extensionAttributes->setProductSku($product->getSku());
            $extensionAttributes->setProductName($product->getName());

            $taxCode = $product->getTaxProviderTaxCode()
                ?? $this->configurations->getDefaultProductTaxProviderTaxCode();
            $extensionAttributes->setProductTaxCode($taxCode);

            $zampPrice = ($product->getTypeId() === Product\Type::TYPE_BUNDLE)
                ? $product->getFinalPrice()
                : $extensionAttributes->getPriceForTaxCalculation();
            $extensionAttributes->setZampPrice((float)$zampPrice);

            $quoteDetailsItem->setExtensionAttributes($extensionAttributes);
        }
        return $quoteDetailsItem;
    }
}
