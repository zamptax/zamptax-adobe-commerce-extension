<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model;

use ATF\Zamp\Model\Transaction\ShipToAddress;
use ATF\Zamp\Model\TransactionObject;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class TransactionObjectTest extends TestCase
{
    public function testBuildShippingToAddressMapsCanadianProvinceAndCountry(): void
    {
        $region = new class {
            public function getRegionId(): int
            {
                return 12;
            }
        };

        $address = $this->createMock(AddressInterface::class);
        $address->method('getStreet')->willReturn(['1 Place Ville Marie']);
        $address->method('getCity')->willReturn('Montreal');
        $address->method('getRegion')->willReturn($region);
        $address->method('getPostcode')->willReturn('H3B 2C4');
        $address->method('getCountryId')->willReturn('CA');

        $request = new DataObject(['zamp_shipping_address' => $address]);

        $shipToAddress = $this->getMockBuilder(ShipToAddress::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setLine1', 'setLine2', 'setCity', 'getRegionCodeById', 'setState', 'setZip', 'setCountry'])
            ->getMock();
        $shipToAddress->method('setLine1')->with('1 Place Ville Marie')->willReturnSelf();
        $shipToAddress->method('setLine2')->with(null)->willReturnSelf();
        $shipToAddress->method('setCity')->with('Montreal')->willReturnSelf();
        $shipToAddress->method('getRegionCodeById')->with(12)->willReturn('QC');
        $shipToAddress->method('setState')->with('QC')->willReturnSelf();
        $shipToAddress->method('setZip')->with('H3B 2C4')->willReturnSelf();
        $shipToAddress->method('setCountry')->with('CA')->willReturnSelf();

        $transactionObject = $this->getMockBuilder(TransactionObject::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createShippingToAddress'])
            ->getMock();
        $transactionObject->method('createShippingToAddress')->willReturn($shipToAddress);

        $this->assertSame($shipToAddress, $transactionObject->buildShippingToAddress($request));
    }

    public function testBuildShippingToAddressSkipsCountryWhenMissing(): void
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getStreet')->willReturn(['123 Guest Street']);
        $address->method('getCity')->willReturn('Toronto');
        $address->method('getRegion')->willReturn(null);
        $address->method('getPostcode')->willReturn('M5H 2N2');
        $address->method('getCountryId')->willReturn(null);

        $request = new DataObject(['zamp_shipping_address' => $address]);

        $shipToAddress = $this->getMockBuilder(ShipToAddress::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setLine1', 'setLine2', 'setCity', 'setZip', 'setCountry'])
            ->getMock();
        $shipToAddress->method('setLine1')->with('123 Guest Street')->willReturnSelf();
        $shipToAddress->method('setLine2')->with(null)->willReturnSelf();
        $shipToAddress->method('setCity')->with('Toronto')->willReturnSelf();
        $shipToAddress->method('setZip')->with('M5H 2N2')->willReturnSelf();
        $shipToAddress->expects($this->never())->method('setCountry');

        $transactionObject = $this->getMockBuilder(TransactionObject::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createShippingToAddress'])
            ->getMock();
        $transactionObject->method('createShippingToAddress')->willReturn($shipToAddress);

        $this->assertSame($shipToAddress, $transactionObject->buildShippingToAddress($request));
    }

    public function testBuildShippingToAddressUsesRegionCodeWhenRegionIdIsUnavailable(): void
    {
        $region = new class {
            public function getRegionCode(): string
            {
                return 'QC';
            }
        };

        $address = $this->createMock(AddressInterface::class);
        $address->method('getStreet')->willReturn(['1 Place Ville Marie']);
        $address->method('getCity')->willReturn('Montreal');
        $address->method('getRegion')->willReturn($region);
        $address->method('getPostcode')->willReturn('H3B 2C4');
        $address->method('getCountryId')->willReturn('CA');

        $shipToAddress = $this->getMockBuilder(ShipToAddress::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setLine1', 'setLine2', 'setCity', 'setState', 'setZip', 'setCountry'])
            ->getMock();
        $shipToAddress->method('setLine1')->with('1 Place Ville Marie')->willReturnSelf();
        $shipToAddress->method('setLine2')->with(null)->willReturnSelf();
        $shipToAddress->method('setCity')->with('Montreal')->willReturnSelf();
        $shipToAddress->method('setState')->with('QC')->willReturnSelf();
        $shipToAddress->method('setZip')->with('H3B 2C4')->willReturnSelf();
        $shipToAddress->method('setCountry')->with('CA')->willReturnSelf();

        $transactionObject = $this->getMockBuilder(TransactionObject::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createShippingToAddress'])
            ->getMock();
        $transactionObject->method('createShippingToAddress')->willReturn($shipToAddress);

        $request = new DataObject(['zamp_shipping_address' => $address]);

        $this->assertSame($shipToAddress, $transactionObject->buildShippingToAddress($request));
    }
}
