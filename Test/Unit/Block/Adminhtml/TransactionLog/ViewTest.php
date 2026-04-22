<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Block\Adminhtml\TransactionLog;

use ATF\Zamp\Block\Adminhtml\TransactionLog\View;
use ATF\Zamp\Model\ResourceModel\TransactionLog as TransactionLogResource;
use ATF\Zamp\Model\TransactionLog;
use ATF\Zamp\Model\TransactionLogFactory;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    /**
     * @var View
     */
    protected $block;

    /**
     * @var TransactionLogFactory|MockObject
     */
    protected $transactionLogFactory;

    /**
     * @var TransactionLogResource|MockObject
     */
    protected $transactionLogResource;

    /**
     * @var TransactionLog|MockObject
     */
    protected $transactionLog;

    /**
     * @var RequestInterface|MockObject
     */
    protected $request;

    protected function setUp(): void
    {
        $this->transactionLogResource = $this->createMock(TransactionLogResource::class);

        $this->transactionLogFactory = $this->createMock(TransactionLogFactory::class);

        $this->transactionLog = $this->createMock(TransactionLog::class);

        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->block = $this->getMockBuilder(View::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequest'])
            ->getMock();

        $this->setProperty($this->block, 'transactionLogResource', $this->transactionLogResource);
        $this->setProperty($this->block, 'transactionLogFactory', $this->transactionLogFactory);
    }

    /**
     * Test getTransactionLogData method
     *
     * @return void
     */
    public function testGetTransactionLogData()
    {
        $this->block->expects($this->once())->method('getRequest')->willReturn($this->request);
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn(1);
        $this->transactionLogFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->transactionLog);

        $this->transactionLogResource
            ->expects($this->once())
            ->method('load')
            ->with($this->transactionLog, 1)
            ->willReturn($this->transactionLog);

        $this->assertEquals($this->transactionLog, $this->block->getTransactionLogData());
    }

    /**
     * Test getStatusLabel method
     *
     * @return void
     */
    public function testGetStatusLabel()
    {
        $this->assertEquals(__('Success'), $this->block->getStatusLabel(1));
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setValue($object, $value);
    }
}
