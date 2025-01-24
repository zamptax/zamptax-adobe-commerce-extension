<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Preference\Model\Calculation;

use ATF\Zamp\Model\Calculation\ZampCalculator;
use InvalidArgumentException;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddress;
use Magento\Tax\Model\Calculation\AbstractCalculator;
use Magento\Tax\Model\Calculation\CalculatorFactory as MageTaxCalculatorFactory;
use Magento\Tax\Model\Calculation\RowBaseCalculator;
use Magento\Tax\Model\Calculation\TotalBaseCalculator;
use Magento\Tax\Model\Calculation\UnitBaseCalculator;

/**
 * Extend class from Magento_Tax module
 * @see MageTaxCalculatorFactory
 */
class CalculatorFactory extends MageTaxCalculatorFactory
{
    /**
     * Identifier constant for unit based calculation
     */
    public const CALC_ZAMP = 'ZAMP_CALCULATION';

    /**
     * @inheritDoc
     */
    public function create(
        $type,
        $storeId,
        CustomerAddress $billingAddress = null,
        CustomerAddress $shippingAddress = null,
        $customerTaxClassId = null,
        $customerId = null
    ) {
        switch ($type) {
            case self::CALC_UNIT_BASE:
                $className = UnitBaseCalculator::class;
                break;
            case self::CALC_ROW_BASE:
                $className = RowBaseCalculator::class;
                break;
            case self::CALC_TOTAL_BASE:
                $className = TotalBaseCalculator::class;
                break;
            case self::CALC_ZAMP:
                $className = ZampCalculator::class;
                break;
            default:
                throw new InvalidArgumentException('Unknown calculation type: ' . $type);
        }

        /** @var AbstractCalculator $calculator */
        $calculator = $this->objectManager->create($className, ['storeId' => $storeId]);
        if (null !== $shippingAddress) {
            $calculator->setShippingAddress($shippingAddress);
        }
        if (null !== $billingAddress) {
            $calculator->setBillingAddress($billingAddress);
        }
        if (null !== $customerTaxClassId) {
            $calculator->setCustomerTaxClassId($customerTaxClassId);
        }
        if (null !== $customerId) {
            $calculator->setCustomerId($customerId);
        }
        return $calculator;
    }
}
