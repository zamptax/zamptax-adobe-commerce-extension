<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Tax\Model\Sales\Total\Quote;

use ATF\Zamp\Model\Extend\ShippingDetails;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

class CommonTaxCollectorShipping
{
    /**
     * @var ShippingDetails
     */
    private ShippingDetails $shippingDetails;

    /**
     * @param ShippingDetails $shippingDetails
     */
    public function __construct(
        ShippingDetails $shippingDetails
    ) {
        $this->shippingDetails = $shippingDetails;
    }

    /**
     * After Get Shipping Data Object
     *
     * @param CommonTaxCollector $subject
     * @param QuoteDetailsItemInterface $result
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return QuoteDetailsItemInterface
     */
    public function afterGetShippingDataObject(
        CommonTaxCollector               $subject,
        QuoteDetailsItemInterface        $result,
        ShippingAssignmentInterface      $shippingAssignment,
        Total                            $total
    ): QuoteDetailsItemInterface {
        return $this->shippingDetails->execute($result, $total);
    }
}
