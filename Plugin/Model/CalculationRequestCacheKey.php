<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Model;

use Magento\Framework\DataObject;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Sales\Quote\ItemDetails;

class CalculationRequestCacheKey
{
    /**
     * Before Get Rate
     *
     * @param Calculation $subject
     * @param DataObject $request
     * @return array
     */
    public function beforeGetRate(
        Calculation $subject,
        DataObject  $request
    ): array {
        if ($request->getZamp()) {

            /** @var ItemDetails $item */
            $item = $request->getZampItem();
            $extension = $item->getExtensionAttributes();
            if ($extension && $extension->getProductTaxCode()) {
                $taxCode = $extension->getProductTaxCode();
                $request->setProductClassId($taxCode);
            }

        } else {
            $request->setProductClassId(null);
        }

        return [$request];
    }
}
