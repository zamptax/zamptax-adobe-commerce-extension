<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Preference\Model;

use ATF\Zamp\Model\Calculate;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\Service\TaxExemptCodeResolver;
use ATF\Zamp\Preference\Model\TaxCalculation;
use ATF\Zamp\Services\Quote as QuoteService;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsInterfaceFactory;
use Magento\Tax\Api\Data\TaxDetailsItemInterfaceFactory;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Calculation\CalculatorFactory;
use Magento\Tax\Model\Config;
use PHPUnit\Framework\TestCase;

class TaxCalculationTest extends TestCase
{
    public function testCalculateTaxFallsBackToMagentoCoreWhenZampIsDisabled(): void
    {
        $taxDetails = $this->createMock(TaxDetailsInterface::class);
        $taxDetails->method('setSubtotal')->with(0.0)->willReturnSelf();
        $taxDetails->method('setTaxAmount')->with(0.0)->willReturnSelf();
        $taxDetails->method('setDiscountTaxCompensationAmount')->with(0.0)->willReturnSelf();
        $taxDetails->method('setAppliedTaxes')->with([])->willReturnSelf();
        $taxDetails->method('setItems')->with([])->willReturnSelf();

        $taxDetailsFactory = $this->createMock(TaxDetailsInterfaceFactory::class);
        $taxDetailsFactory->expects($this->once())->method('create')->willReturn($taxDetails);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->expects($this->never())->method('getStore');

        $zampConfigurations = $this->createMock(Configurations::class);
        $zampConfigurations->expects($this->once())->method('isModuleEnabled')->willReturn(false);
        $zampConfigurations->expects($this->never())->method('isCalculationEnabled');

        $zampCalculate = $this->createMock(Calculate::class);
        $zampCalculate->expects($this->never())->method('execute');

        $taxExemptCodeResolver = $this->createMock(TaxExemptCodeResolver::class);
        $taxExemptCodeResolver->expects($this->never())->method('execute');

        $quoteDetails = $this->createMock(QuoteDetailsInterface::class);
        $quoteDetails->expects($this->once())->method('getItems')->willReturn([]);

        $subject = new TaxCalculation(
            $this->createMock(Calculation::class),
            $this->createMock(CalculatorFactory::class),
            $this->createMock(Config::class),
            $taxDetailsFactory,
            $this->createMock(TaxDetailsItemInterfaceFactory::class),
            $storeManager,
            $this->createMock(TaxClassManagementInterface::class),
            $this->createMock(DataObjectHelper::class),
            $zampConfigurations,
            $zampCalculate,
            $this->createMock(Json::class),
            $this->createMock(QuoteService::class),
            $taxExemptCodeResolver
        );

        $this->assertSame($taxDetails, $subject->calculateTax($quoteDetails, 1));
    }

    public function testCalculateTaxUsesZampPathWhenEnabledWithShippingAddress(): void
    {
        $taxDetails = $this->createMock(TaxDetailsInterface::class);
        $taxDetails->method('setSubtotal')->with(0.0)->willReturnSelf();
        $taxDetails->method('setTaxAmount')->with(0.0)->willReturnSelf();
        $taxDetails->method('setDiscountTaxCompensationAmount')->with(0.0)->willReturnSelf();
        $taxDetails->method('setAppliedTaxes')->with([])->willReturnSelf();
        $taxDetails->method('setItems')->with([])->willReturnSelf();

        $taxDetailsFactory = $this->createMock(TaxDetailsInterfaceFactory::class);
        $taxDetailsFactory->expects($this->once())->method('create')->willReturn($taxDetails);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->expects($this->never())->method('getStore');

        $zampConfigurations = $this->createMock(Configurations::class);
        $zampConfigurations->expects($this->once())->method('isModuleEnabled')->willReturn(true);
        $zampConfigurations->expects($this->once())->method('isCalculationEnabled')->willReturn(true);

        $zampCalculate = $this->createMock(Calculate::class);
        $zampCalculate->expects($this->never())->method('execute');

        $shippingAddress = new class {
            public function getCountryId(): string
            {
                return 'CA';
            }
        };

        $quoteDetails = $this->createMock(QuoteDetailsInterface::class);
        $quoteDetails->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddress);
        $quoteDetails->expects($this->once())->method('getItems')->willReturn([]);

        $subject = new TaxCalculation(
            $this->createMock(Calculation::class),
            $this->createMock(CalculatorFactory::class),
            $this->createMock(Config::class),
            $taxDetailsFactory,
            $this->createMock(TaxDetailsItemInterfaceFactory::class),
            $storeManager,
            $this->createMock(TaxClassManagementInterface::class),
            $this->createMock(DataObjectHelper::class),
            $zampConfigurations,
            $zampCalculate,
            $this->createMock(Json::class),
            $this->createMock(QuoteService::class),
            $this->createMock(TaxExemptCodeResolver::class)
        );

        $this->assertSame($taxDetails, $subject->calculateTax($quoteDetails, 1));
        $this->assertTrue($subject->isZampCalculation());
    }

    public function testCalculateTaxFallsBackToMagentoCoreWhenShippingCountryIsMissing(): void
    {
        $taxDetails = $this->createMock(TaxDetailsInterface::class);
        $taxDetails->method('setSubtotal')->with(0.0)->willReturnSelf();
        $taxDetails->method('setTaxAmount')->with(0.0)->willReturnSelf();
        $taxDetails->method('setDiscountTaxCompensationAmount')->with(0.0)->willReturnSelf();
        $taxDetails->method('setAppliedTaxes')->with([])->willReturnSelf();
        $taxDetails->method('setItems')->with([])->willReturnSelf();

        $taxDetailsFactory = $this->createMock(TaxDetailsInterfaceFactory::class);
        $taxDetailsFactory->expects($this->once())->method('create')->willReturn($taxDetails);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->expects($this->never())->method('getStore');

        $zampConfigurations = $this->createMock(Configurations::class);
        $zampConfigurations->expects($this->once())->method('isModuleEnabled')->willReturn(true);
        $zampConfigurations->expects($this->once())->method('isCalculationEnabled')->willReturn(true);

        $zampCalculate = $this->createMock(Calculate::class);
        $zampCalculate->expects($this->never())->method('execute');

        $shippingAddress = new class {
            public function getCountryId(): ?string
            {
                return null;
            }
        };

        $quoteDetails = $this->createMock(QuoteDetailsInterface::class);
        $quoteDetails->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddress);
        $quoteDetails->expects($this->once())->method('getItems')->willReturn([]);

        $subject = new TaxCalculation(
            $this->createMock(Calculation::class),
            $this->createMock(CalculatorFactory::class),
            $this->createMock(Config::class),
            $taxDetailsFactory,
            $this->createMock(TaxDetailsItemInterfaceFactory::class),
            $storeManager,
            $this->createMock(TaxClassManagementInterface::class),
            $this->createMock(DataObjectHelper::class),
            $zampConfigurations,
            $zampCalculate,
            $this->createMock(Json::class),
            $this->createMock(QuoteService::class),
            $this->createMock(TaxExemptCodeResolver::class)
        );

        $this->assertSame($taxDetails, $subject->calculateTax($quoteDetails, 1));
    }
}
