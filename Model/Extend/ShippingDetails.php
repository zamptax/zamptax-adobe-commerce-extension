<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Extend;

use ATF\Zamp\Model\Configurations;
use Magento\Catalog\Model\Product\Type;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class ShippingDetails
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
     * Add shipping product tax code to include the shipping in tax calculation
     *
     * @param QuoteDetailsItemInterface $quoteDetailsItem
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return QuoteDetailsItemInterface
     */
    public function execute(
        QuoteDetailsItemInterface $quoteDetailsItem,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): QuoteDetailsItemInterface {

        $doZampCalc = $this->configurations->isModuleEnabled()
            && $this->configurations->isCalculationEnabled();

        if (!$doZampCalc) {
            return $quoteDetailsItem;
        }

        if (!$quoteDetailsItem->getExtensionAttributes()) {
            return $quoteDetailsItem;
        }

        $quote =  $shippingAssignment->getShipping()->getAddress()->getQuote();
        $zampItems = [];
        if ($items = $quote->getItems()) {
            foreach ($items as $item) {
                $product = $item->getProduct();
                $productId = $product->getId();
                if ($item->getProductType() === Configurable::TYPE_CODE && $item->getChildren()) {
                    $children = $item->getChildren();
                    $child = reset($children);
                    $child = $child->getProduct();
                    $productId = $product->getId() . '-' . $child->getId();
                } elseif ($product->getTypeId() !== Grouped::TYPE_CODE
                    && $product->getTypeId() !== BundleType::TYPE_CODE
                    && $item->getParentItem()) {
                    $productId = $item->getParentItem()->getProduct()->getId() . '-' . $product->getId();
                }

                $taxCode = $product->getTaxProviderTaxCode()
                    ?? $this->configurations->getDefaultProductTaxProviderTaxCode();
                $zampPrice = ($product->getTypeId() === Type::TYPE_BUNDLE)
                    ? $product->getFinalPrice()
                    : $item->getOriginalPrice();

                $zampItems[$productId] = [
                    'productId' => $productId,
                    'productSku' => $product->getSku(),
                    'productName' => $product->getName(),
                    'productTaxCode' => $taxCode,
                    'zampPrice' => $zampPrice,
                    'discount' => $item->getDiscountAmount(),
                    'quantity' => $item->getQty(),
                    'fromShipping' => true
                ];
            }
        }

        $extensionAttributes = $quoteDetailsItem->getExtensionAttributes();
        if ($total->getShippingTaxCalculationAmount() !== null) {
            $extensionAttributes->setProductTaxCode(
                $this->configurations->getDefaultProductTaxProviderTaxCode()
            );
            $extensionAttributes->setZampItems($zampItems);
            $quoteDetailsItem->setExtensionAttributes($extensionAttributes);
        }

        return $quoteDetailsItem;
    }
}
