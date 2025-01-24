<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Calculation;

use ATF\Zamp\Model\Configurations;
use Magento\Framework\DataObject;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Calculation\AbstractAggregateCalculator;

class ZampCalculator extends AbstractAggregateCalculator
{
    /**
     * @var QuoteDetailsItemInterface
     */
    protected QuoteDetailsItemInterface $itemForZamp;

    /**
     * Address rate request
     *
     * Request object contain:
     *  country_id (->getCountryId())
     *  region_id (->getRegionId())
     *  postcode (->getPostcode())
     *  customer_class_id (->getCustomerClassId())
     *  store (->getStore())
     *  zamp (->getZamp())
     *
     * @var DataObject|null
     */
    private ?DataObject $addressRateRequest = null;

    /**
     * @inheritDoc
     */
    protected function roundAmount(
        $amount,
        $rate = null,
        $direction = null,
        $type = self::KEY_REGULAR_DELTA_ROUNDING,
        $round = true,
        $item = null
    ) {
        return $this->deltaRound($amount, $rate, $direction, $type, $round);
    }

    /**
     * Get Address Rate Request
     *
     * @return DataObject|null
     */
    protected function getAddressRateRequest(): ?DataObject
    {
        if (null === $this->addressRateRequest) {
            $this->addressRateRequest = $this->calculationTool->getRateRequest(
                $this->shippingAddress,
                $this->billingAddress,
                $this->customerTaxClassId,
                $this->storeId,
                $this->customerId
            );
        }

        $this->addressRateRequest->setData(Configurations::REQUEST_ZAMP, true);
        $this->addressRateRequest->setData(Configurations::REQUEST_ZAMP_ITEM, $this->getItemForZamp());
        $this->addressRateRequest->setData(Configurations::REQUEST_ZAMP_SHIPPING_ADDRESS, $this->shippingAddress);

        return $this->addressRateRequest;
    }

    /**
     * GetI tem For Zamp
     *
     * @return QuoteDetailsItemInterface
     */
    public function getItemForZamp(): QuoteDetailsItemInterface
    {
        return $this->itemForZamp;
    }

    /**
     * Set Item For Zamp
     *
     * @param QuoteDetailsItemInterface $itemForZamp
     * @return void
     */
    public function setItemForZamp(QuoteDetailsItemInterface $itemForZamp): void
    {
        $this->itemForZamp = $itemForZamp;
    }

    /**
     * @inheritDoc
     */
    protected function calculateWithTaxInPrice(QuoteDetailsItemInterface $item, $quantity, $round = true)
    {
        $this->setItemForZamp($item);
        return parent::calculateWithTaxInPrice($item, $quantity, $round);
    }

    /**
     * @inheritDoc
     */
    protected function calculateWithTaxNotInPrice(QuoteDetailsItemInterface $item, $quantity, $round = true)
    {
        $this->setItemForZamp($item);
        return parent::calculateWithTaxNotInPrice($item, $quantity, $round);
    }
}
