<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Observer;

use ATF\Zamp\Model\Calculate;
use ATF\Zamp\Model\Configurations;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Sales\Total\Quote\Shipping;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class ImplementTaxRateZamp implements ObserverInterface
{
    /**
     * @var Calculate
     */
    protected Calculate $calculate;

    /**
     * @var Json
     */
    protected Json $jsonSerializer;

    /**
     * @param Calculate $calculate
     * @param Json $jsonSerializer
     */
    public function __construct(
        Calculate $calculate,
        Json      $jsonSerializer
    ) {
        $this->calculate = $calculate;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Observer for tax_rate_data_fetch
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        /** @var DataObject $request */
        $request = $event->getData('request');

        /** @var Calculation $sender */
        $sender = $event->getData('sender');

        if ($request->getData(Configurations::REQUEST_ZAMP)) {

            /** @var QuoteDetailsItemInterface $item */
            $item = $request->getData(Configurations::REQUEST_ZAMP_ITEM);

            if (($extension = $item->getExtensionAttributes()) && $extension->getZampTaxInfo()) {
                $taxInfo = $this->jsonSerializer->unserialize($extension->getZampTaxInfo());
                $sender->setRateId($taxInfo['rateId']);
                $sender->setRateTitle($taxInfo['rateTitle']);
                $rateValue = 0;
                if ($item->getType() === Shipping::ITEM_CODE_SHIPPING) {
                    foreach ($taxInfo['taxes'] as $tax) {
                        if (isset($tax['taxRate']) && $tax['ancillaryType'] === 'SHIPPING_HANDLING') {
                            $rateValue += $tax['taxRate'] * 100;
                        }
                    }
                } else {
                    foreach ($taxInfo['taxes'] as $tax) {
                        if (isset($tax['taxRate']) && $tax['ancillaryType'] !== 'SHIPPING_HANDLING') {
                            $rateValue += $tax['taxRate'] * 100;
                        }
                    }
                }

                $sender->setRateValue($rateValue);
            }
        }
    }
}
