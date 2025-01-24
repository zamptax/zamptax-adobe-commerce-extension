<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model;

use ATF\Zamp\Model\Refund;
use ATF\Zamp\Model\Sales\CommentHandler;
use ATF\Zamp\Model\Service\Transaction;
use ATF\Zamp\Model\Transaction\PayloadItems;
use ATF\Zamp\Model\TransactionObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test refund transaction
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RefundTest extends TestCase
{
    /**
     * @var Refund
     */
    private $refund;

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

        $this->refund = new Refund(
            $this->transactionObjectFactoryMock,
            $this->transactionServiceMock,
            $this->resourceConnectionMock,
            $this->payloadItems,
            $this->commentHandlerMock
        );
    }

    /**
     * Test the execute method
     */
    public function testExecute(): void
    {
        // Create mocks for Creditmemo, Invoice, and Order
        $creditMemoMock = $this->createMock(Creditmemo::class);
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->addMethods(['getInvoiceCollection', 'getFirstItem', 'getShippingAddress', 'getZampCustomerTaxExemptCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->addMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $invoiceItemMock = $this->getMockBuilder(InvoiceItemInterface::class)
            ->addMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        // Prepare credit memo and order data
        $creditMemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditMemoMock->expects($this->exactly(2))->method('getIncrementId')->willReturn('100000001');
        $creditMemoMock->expects($this->once())->method('getShippingAmount')->willReturn(10.00);
        $creditMemoMock->expects($this->once())->method('getSubTotal')->willReturn(100.00);
        $creditMemoMock->expects($this->once())->method('getDiscountAmount')->willReturn(5.00);
        $creditMemoMock->expects($this->once())->method('getTaxAmount')->willReturn(8.00);
        $creditMemoMock->expects($this->once())->method('getAllItems')->willReturn([$invoiceItemMock]);
        $creditMemoMock->expects($this->exactly(2))->method('getId')->willReturn(1);

        // Mock invoice collection
        $orderMock->expects($this->once())->method('getInvoiceCollection')->willReturnSelf();
        $orderMock->expects($this->once())->method('getFirstItem')->willReturn($invoiceMock);
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn('Test Shipping Address');
        $orderMock->expects($this->once())->method('getZampCustomerTaxExemptCode')->willReturn('FEDERAL_GOV');

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
            ->with($this->isInstanceOf(DataObject::class), 'refund')
            ->willReturn($transactionObjMock);
        $transactionObjMock->expects($this->once())
            ->method('toArray')
            ->willReturn(['payload_data']);

        // Mock transaction service response
        $this->transactionServiceMock->expects($this->once())
            ->method('createRefundTransaction')
            ->with(['payload_data'])
            ->willReturn(['id' => 'CM-1']);

        // Expect saveZampTransId to be called
        $this->connectionMock->expects($this->exactly(2))->method('update');

        $this->commentHandlerMock->expects($this->once())
            ->method('addCreditmemoComment')
            ->with(1, 'CM-1');

        // Execute the refund process and assert the result
        $result = $this->refund->execute($creditMemoMock);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('CM-1', $result['id']);
    }
}
