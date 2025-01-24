<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model;

use ATF\Zamp\Model\Transact;
use ATF\Zamp\Model\Sales\CommentHandler;
use ATF\Zamp\Model\Service\Transaction;
use ATF\Zamp\Model\Transaction\PayloadItems;
use ATF\Zamp\Model\TransactionObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test transaction creation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactTest extends TestCase
{
    /**
     * @var Transact
     */
    private $transact;

    /**
     * @var MockObject|TransactionObjectFactory
     */
    private $transactionObjectFactoryMock;

    /**
     * @var MockObject|Transaction
     */
    private $transactionServiceMock;

    /**
     * @var MockObject|AdapterInterface
     */
    private $connectionMock;

    /**
     * @var MockObject|ResourceConnection
     */
    private $resourceConnectionMock;

    /**
     * @var PayloadItems|MockObject
     */
    private $payloadItems;

    /**
     * @var CommentHandler|MockObject
     */
    private $commentHandlerMock;

    /**
     * Setup method for creating necessary mocks
     */
    protected function setUp(): void
    {
        $this->transactionObjectFactoryMock = $this->createMock(TransactionObjectFactory::class);
        $this->transactionServiceMock = $this->createMock(Transaction::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->payloadItems = $this->createMock(PayloadItems::class);
        $this->commentHandlerMock = $this->createMock(CommentHandler::class);

        $this->resourceConnectionMock->method('getConnection')->willReturn($this->connectionMock);

        $this->transact = new Transact(
            $this->transactionObjectFactoryMock,
            $this->transactionServiceMock,
            $this->resourceConnectionMock,
            $this->payloadItems,
            $this->commentHandlerMock
        );
    }

    public function testExecuteSuccess()
    {
        // Create mocks for Invoice and Order
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->addMethods(['getZampCustomerTaxExemptCode', 'getFirstItem', 'getShippingAddress'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        // Mock the item methods
        $invoiceItemMock = $this->getMockBuilder(InvoiceItemInterface::class)
            ->addMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->onlyMethods(['getIncrementId'])
            ->addMethods(['getId', 'getAllItems', 'getOrder'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $invoiceMock->expects($this->exactly(2))->method('getId')->willReturn(1);
        $invoiceMock->expects($this->exactly(2))->method('getIncrementId')->willReturn('000001');
        $invoiceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $invoiceMock->expects($this->once())->method('getAllItems')->willReturn([$invoiceItemMock]);

        // Mock TransactionObjectFactory and TransactionObject creation
        $transactionObjMock = $this->getMockBuilder(DataObject::class)
            ->onlyMethods(['toArray'])
            ->addMethods(['createPayload'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionObjectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($transactionObjMock);
        $transactionObjMock->expects($this->once())
            ->method('createPayload')
            ->with($this->isInstanceOf(DataObject::class), 'invoice')
            ->willReturn($transactionObjMock);
        $transactionObjMock->expects($this->once())
            ->method('toArray')
            ->willReturn(['payload_data']);

        // Mock transaction service response
        $this->transactionServiceMock->expects($this->once())
            ->method('createTransaction')
            ->with(['payload_data'])
            ->willReturn(['id' => 'INV-1']);

        // Expect saveZampTransId to be called
        $this->connectionMock->expects($this->exactly(2))->method('update');

        $this->commentHandlerMock->expects($this->once())
            ->method('addInvoiceComment')
            ->with(1, 'INV-1');

        // Execute the refund process and assert the result
        $result = $this->transact->execute($invoiceMock);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('INV-1', $result['id']);
    }

    public function testExecuteThrowsException()
    {
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->addMethods(['getZampCustomerTaxExemptCode', 'getShippingAddress'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderMock->expects($this->once())->method('getZampCustomerTaxExemptCode')->willReturn('R_TPP');

        $invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->addMethods(['getId', 'getAllItems', 'getOrder'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $invoiceMock->expects($this->once())->method('getId')->willReturn(1);
        $invoiceMock->expects($this->once())->method('getIncrementId')->willReturn(000001);
        $invoiceMock->expects($this->once())->method('getShippingAmount')->willReturn(10);
        $invoiceMock->expects($this->once())->method('getSubTotal')->willReturn(120);
        $invoiceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $invoiceItemMock = $this->createMock(InvoiceItemInterface::class);
        $invoiceMock->expects($this->once())->method('getAllItems')->willReturn([$invoiceItemMock]);

        $transactionObjMock = $this->getMockBuilder(DataObject::class)
            ->onlyMethods(['toArray'])
            ->addMethods(['createPayload'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionObjectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($transactionObjMock);
        $transactionObjMock->expects($this->once())
            ->method('createPayload')
            ->with($this->isInstanceOf(DataObject::class), 'invoice')
            ->willThrowException(new LocalizedException(__('Error creating payload')));

        // Expect the exception when the execute method is called
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Error creating payload');

        // Execute the method and expect an exception
        $this->transact->execute($invoiceMock);
    }
}
