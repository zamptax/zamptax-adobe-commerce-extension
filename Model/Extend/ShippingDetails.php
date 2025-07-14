<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Extend;

use ATF\Zamp\Model\Configurations;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;

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
     * @param Total $total
     * @return QuoteDetailsItemInterface
     */
    public function execute(
        QuoteDetailsItemInterface $quoteDetailsItem,
        Total $total
    ): QuoteDetailsItemInterface {
        if ($extensionAttributes = $quoteDetailsItem->getExtensionAttributes()) {
            if ($total->getShippingTaxCalculationAmount() !== null) {
                $extensionAttributes->setProductTaxCode(
                    $this->configurations->getDefaultProductTaxProviderTaxCode()
                );
                $quoteDetailsItem->setExtensionAttributes($extensionAttributes);
            }
        }

        return $quoteDetailsItem;
    }
}
