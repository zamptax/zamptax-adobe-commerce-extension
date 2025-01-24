<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model\Transaction;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ATF\Zamp\Model\Transaction\PayloadItems;

/**
 * Unit Test for PayloadItems class
 *
 * @see PayloadItems
 */
class PayloadItemsTest extends TestCase
{
    /**
     * @var PayloadItems
     */
    protected PayloadItems $payloadItems;

    protected function setUp(): void
    {
        $this->payloadItems = new PayloadItems();
    }

    public function testExecuteSimple(): void
    {
        $simpleProductMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getSku', 'getPrice', 'getRowTotal', 'setProductId'])
            ->getMockForAbstractClass();

        $simpleProductMock->expects($this->once())->method('getSku')->willReturn('abc123');
        $simpleProductMock->expects($this->once())->method('getPrice')->willReturn(20.00);
        $simpleProductMock->expects($this->once())->method('getRowTotal')->willReturn(20.00);

        $simpleProductMock->expects($this->never())->method('setProductId')->willReturn(20.00);

        $items = [
            $simpleProductMock
        ];

        $result = $this->payloadItems->execute($items);

        $this->assertNotEmpty($result);
    }

    public function testExecuteConfigurable(): void
    {
        $simpleProductMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getSku', 'getPrice', 'getRowTotal', 'getProductId', 'setProductId'])
            ->getMockForAbstractClass();

        $simpleProductMock->method('getSku')->willReturn('SKU-SMALL-RED');
        $simpleProductMock->expects($this->once())->method('getPrice')->willReturn(20.00);
        $simpleProductMock->expects($this->once())->method('getRowTotal')->willReturn(20.00);
        $simpleProductMock->expects($this->never())->method('getProductId')->willReturn(1259);
        $simpleProductMock->expects($this->once())->method('setProductId')->willReturn(1260);

        $simpleProductMock2 = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getSku', 'getPrice', 'getRowTotal', 'getProductId', 'setProductId'])
            ->getMockForAbstractClass();

        $simpleProductMock2->method('getSku')->willReturn('SKU-SMALL-RED');
        $simpleProductMock2->expects($this->once())->method('getPrice')->willReturn(0.00);
        $simpleProductMock2->expects($this->never())->method('getRowTotal')->willReturn(0.00);

        $simpleProductMock2->expects($this->once())->method('getProductId')->willReturn(1259);
        $simpleProductMock->expects($this->once())->method('setProductId')->willReturn(1261);

        $items = [
            $simpleProductMock,
            $simpleProductMock2,
        ];

        $result = $this->payloadItems->execute($items);

        $this->assertNotEmpty($result);
    }
}
