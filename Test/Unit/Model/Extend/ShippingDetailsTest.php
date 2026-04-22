<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model\Extend;

use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\Extend\ShippingDetails;
use Magento\Catalog\Model\Product;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingDetailsTest extends TestCase
{
    private Configurations|MockObject $configurations;

    private QuoteDetailsItemExtensionFactory|MockObject $extensionFactory;

    private ShippingDetails $shippingDetails;

    protected function setUp(): void
    {
        $this->configurations = $this->createMock(Configurations::class);
        $this->extensionFactory = $this->createMock(QuoteDetailsItemExtensionFactory::class);

        $this->shippingDetails = new ShippingDetails(
            $this->configurations,
            $this->extensionFactory
        );
    }

    public function testExecuteCreatesExtensionAttributesForShippingItem(): void
    {
        $this->configurations->expects($this->once())->method('isModuleEnabled')->willReturn(true);
        $this->configurations->expects($this->once())->method('isCalculationEnabled')->willReturn(true);
        $this->configurations->expects($this->once())
            ->method('getDefaultProductTaxProviderTaxCode')
            ->willReturn('R_TPP');

        /** @var Product&MockObject $product */
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSku', 'getName', 'getTypeId'])
            ->addMethods(['getTaxProviderTaxCode'])
            ->getMock();
        $product->method('getId')->willReturn(99);
        $product->method('getSku')->willReturn('24-MB01');
        $product->method('getName')->willReturn('Joust Duffle Bag');
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getTaxProviderTaxCode')->willReturn('R_TPP');

        $item = new class ($product) extends AbstractItem {
            public function __construct(private readonly Product $product)
            {
            }

            public function getQuote()
            {
                return null;
            }

            public function getAddress()
            {
                return null;
            }

            public function getProduct()
            {
                return $this->product;
            }

            public function getProductType()
            {
                return 'simple';
            }

            public function getChildren()
            {
                return [];
            }

            public function getParentItem()
            {
                return null;
            }

            public function getOriginalPrice()
            {
                return 31.95;
            }

            public function getDiscountAmount()
            {
                return 0.0;
            }

            public function getQty()
            {
                return 1;
            }

            public function getOptionByCode($code)
            {
                return null;
            }
        };

        $quote = $this->createConfiguredMock(Quote::class, [
            'getItems' => [$item],
        ]);

        $address = $this->createConfiguredMock(Address::class, [
            'getQuote' => $quote,
        ]);

        $shipping = $this->createConfiguredMock(ShippingInterface::class, [
            'getAddress' => $address,
        ]);

        $shippingAssignment = $this->createConfiguredMock(ShippingAssignmentInterface::class, [
            'getShipping' => $shipping,
        ]);

        $extensionAttributes = $this->createMock(QuoteDetailsItemExtensionInterface::class);
        $extensionAttributes->expects($this->once())
            ->method('setProductTaxCode')
            ->with('R_TPP')
            ->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('setZampItems')
            ->with([
                99 => [
                    'productId' => 99,
                    'productSku' => '24-MB01',
                    'productName' => 'Joust Duffle Bag',
                    'productTaxCode' => 'R_TPP',
                    'zampPrice' => 31.95,
                    'discount' => 0.0,
                    'quantity' => 1,
                    'fromShipping' => true,
                ],
            ])
            ->willReturnSelf();

        $this->extensionFactory->expects($this->once())->method('create')->willReturn($extensionAttributes);

        $quoteDetailsItem = $this->createMock(QuoteDetailsItemInterface::class);
        $quoteDetailsItem->expects($this->once())->method('getExtensionAttributes')->willReturn(null);
        $quoteDetailsItem->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($extensionAttributes)
            ->willReturnSelf();

        $result = $this->shippingDetails->execute(
            $quoteDetailsItem,
            $shippingAssignment,
            $this->createMock(Total::class)
        );

        $this->assertSame($quoteDetailsItem, $result);
    }
}
