<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Observer;

use ATF\Zamp\Model\Calculate;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Observer\ImplementTaxRateZamp;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Sales\Quote\ItemDetails;
use Magento\Tax\Model\Sales\Total\Quote\Shipping;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImplementTaxRateZampTest extends TestCase
{
    public function testExecuteDeduplicatesDistributedShippingTaxRates(): void
    {
        $extensionAttributes = new class {
            public function getZampTaxInfo(): string
            {
                return 'serialized-tax-info';
            }
        };

        /** @var ItemDetails&MockObject $item */
        $item = $this->getMockBuilder(ItemDetails::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensionAttributes', 'getType'])
            ->getMock();
        $item->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $item->method('getType')->willReturn(Shipping::ITEM_CODE_SHIPPING);

        $request = new DataObject([
            Configurations::REQUEST_ZAMP => true,
            Configurations::REQUEST_ZAMP_ITEM => $item,
        ]);

        /** @var Calculation&MockObject $sender */
        $sender = $this->getMockBuilder(Calculation::class)
            ->disableOriginalConstructor()
            ->addMethods(['setRateId', 'setRateTitle', 'setRateValue'])
            ->getMock();
        $sender->expects($this->once())->method('setRateId')->with('shipping-rate');
        $sender->expects($this->once())->method('setRateTitle')->with('shipping-rate');
        $sender->expects($this->once())->method('setRateValue')->with(
            $this->callback(function ($rateValue): bool {
                $this->assertEqualsWithDelta(14.975, (float)$rateValue, 0.00001);
                return true;
            })
        );

        $json = $this->createMock(Json::class);
        $json->expects($this->once())->method('unserialize')->with('serialized-tax-info')->willReturn([
            'rateId' => 'shipping-rate',
            'rateTitle' => 'shipping-rate',
            'taxes' => [
                [
                    'jurisdictionCode' => 'CAN.P.QC',
                    'compositeCode' => 'GST',
                    'ancillaryType' => 'SHIPPING_HANDLING',
                    'taxRate' => 0.05,
                ],
                [
                    'jurisdictionCode' => 'CAN.P.QC',
                    'compositeCode' => 'QST',
                    'ancillaryType' => 'SHIPPING_HANDLING',
                    'taxRate' => 0.09975,
                ],
                [
                    'jurisdictionCode' => 'CAN.P.QC',
                    'compositeCode' => 'GST',
                    'ancillaryType' => 'SHIPPING_HANDLING',
                    'taxRate' => 0.05,
                ],
                [
                    'jurisdictionCode' => 'CAN.P.QC',
                    'compositeCode' => 'QST',
                    'ancillaryType' => 'SHIPPING_HANDLING',
                    'taxRate' => 0.09975,
                ],
            ],
        ]);

        $observer = new Observer([
            'event' => new DataObject([
                'request' => $request,
                'sender' => $sender,
            ]),
        ]);

        $subject = new ImplementTaxRateZamp(
            $this->createMock(Calculate::class),
            $json
        );

        $subject->execute($observer);

        $this->addToAssertionCount(1);
    }
}
