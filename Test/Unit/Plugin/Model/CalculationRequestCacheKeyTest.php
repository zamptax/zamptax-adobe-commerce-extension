<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Plugin\Model;

use ATF\Zamp\Plugin\Model\CalculationRequestCacheKey;
use Magento\Framework\DataObject;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Sales\Quote\ItemDetails;
use Magento\Tax\Model\Sales\Total\Quote\Shipping;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CalculationRequestCacheKeyTest extends TestCase
{
    private CalculationRequestCacheKey $plugin;

    protected function setUp(): void
    {
        $this->plugin = new CalculationRequestCacheKey();
    }

    public function testBeforeGetRateKeepsMagentoProductClassForNonZampRequests(): void
    {
        $request = new DataObject(['product_class_id' => 7]);

        $result = $this->plugin->beforeGetRate(
            $this->createMock(Calculation::class),
            $request
        );

        $this->assertSame([$request], $result);
        $this->assertSame(7, $request->getProductClassId());
    }

    public function testBeforeGetRateUsesZampProductTaxCodeForZampRequests(): void
    {
        $extensionAttributes = new class {
            public function getProductTaxCode(): string
            {
                return 'R_TPP';
            }
        };

        /** @var ItemDetails&MockObject $item */
        $item = $this->getMockBuilder(ItemDetails::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensionAttributes'])
            ->getMock();
        $item->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $request = new DataObject([
            'zamp' => true,
            'zamp_item' => $item,
            'product_class_id' => 7,
            'country_id' => 'US',
            'region_id' => 'OR',
            'postcode' => '97232',
        ]);

        $result = $this->plugin->beforeGetRate(
            $this->createMock(Calculation::class),
            $request
        );

        $this->assertSame([$request], $result);
        $this->assertSame('R_TPP|US|OR|97232', $request->getProductClassId());
    }

    public function testBeforeGetRateUsesDedicatedCacheKeyForShippingItem(): void
    {
        /** @var ItemDetails&MockObject $item */
        $item = $this->getMockBuilder(ItemDetails::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getType', 'getExtensionAttributes'])
            ->getMock();
        $item->method('getType')->willReturn(Shipping::ITEM_CODE_SHIPPING);
        $item->expects($this->never())->method('getExtensionAttributes');

        $request = new DataObject([
            'zamp' => true,
            'zamp_item' => $item,
            'product_class_id' => 7,
            'country_id' => 'CA',
            'region_id' => 'QC',
            'postcode' => 'H3B2C4',
        ]);

        $result = $this->plugin->beforeGetRate(
            $this->createMock(Calculation::class),
            $request
        );

        $this->assertSame([$request], $result);
        $this->assertSame('SHIPPING_HANDLING|CA|QC|H3B2C4', $request->getProductClassId());
    }
}
