<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Plugin\Quote;

use ATF\Zamp\Plugin\Quote\CopyProductCodeToSalesOrderItem;
use ATF\Zamp\Model\Configurations;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\TestCase;

class CopyProductCodeToSalesOrderItemTest extends TestCase
{
    /**
     * @var Configurations
     */
    private $configMock;

    /**
     * @var AbstractItem
     */
    private $quoteItemMock;

    /**
     * @var Product
     */
    private $productMock;

    /**
     * @var CopyProductCodeToSalesOrderItem
     */
    private $plugin;

    protected function setUp(): void
    {
        // Mock the Configurations dependency
        $this->configMock = $this->createMock(Configurations::class);

        $this->quoteItemMock = $this->createMock(AbstractItem::class);

        $this->productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getTaxProviderTaxCode'])
            ->disableOriginalConstructor()
            ->getMock();

        // Initialize the plugin with the mocked configuration
        $this->plugin = new CopyProductCodeToSalesOrderItem($this->configMock);
    }

    public function testAroundConvertSetsProductTaxCodeFromProduct()
    {
        $productTaxCode = 'TAX_CODE_123';

        // Mock the product object and the quote item
        $this->productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getTaxProviderTaxCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock->expects($this->once())
            ->method('getTaxProviderTaxCode')
            ->willReturn($productTaxCode);

        $this->quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        // Mock the sales order item and proceed callback
        $orderItemMock = $this->getMockBuilder(Item::class)
            ->addMethods(['setTaxProviderTaxCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemMock->expects($this->once())
            ->method('setTaxProviderTaxCode')
            ->with($productTaxCode);

        $proceedMock = function () use ($orderItemMock) {
            return $orderItemMock;
        };

        // Execute the plugin's aroundConvert method
        $result = $this->plugin->aroundConvert(
            $this->createMock(ToOrderItem::class),
            $proceedMock,
            $this->quoteItemMock
        );

        $this->assertSame($orderItemMock, $result);
    }

    public function testAroundConvertSetsDefaultProductTaxCodeWhenProductTaxCodeIsEmpty()
    {
        $defaultTaxCode = 'DEFAULT_TAX_CODE';

        // Mock the product with an empty tax code
        $this->productMock->expects($this->once())
            ->method('getTaxProviderTaxCode')
            ->willReturn('');

        // Mock the quote item to return the product
        $this->quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        // Mock configuration to return a default tax code
        $this->configMock->expects($this->once())
            ->method('getDefaultProductTaxProviderTaxCode')
            ->willReturn($defaultTaxCode);

        // Mock the order item and proceed callback
        $orderItemMock = $this->getMockBuilder(Item::class)
            ->addMethods(['setTaxProviderTaxCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemMock->expects($this->once())
            ->method('setTaxProviderTaxCode')
            ->with($defaultTaxCode);

        $proceedMock = function () use ($orderItemMock) {
            return $orderItemMock;
        };

        // Execute the plugin's aroundConvert method
        $result = $this->plugin->aroundConvert(
            $this->createMock(ToOrderItem::class),
            $proceedMock,
            $this->quoteItemMock
        );

        $this->assertSame($orderItemMock, $result);
    }
}
