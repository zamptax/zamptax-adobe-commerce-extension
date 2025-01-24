<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model;

use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\HistoricalTransactionSyncQueue as Queue;
use ATF\Zamp\Model\HistoricalTransactionSyncQueueFactory as QueueFactory;
use ATF\Zamp\Model\QueueHandler;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue\CollectionFactory as QueueCollectionFactory;
use ATF\Zamp\Model\Transaction\PayloadItems;
use ATF\Zamp\Model\TransactionObject;
use ATF\Zamp\Model\TransactionObjectFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QueueHandlerTest extends TestCase
{
    /**
     * @var QueueHandler
     */
    private $queueHandler;

    /**
     * @var QueueFactory|MockObject
     */
    protected $queueFactory;

    /**
     * @var Queue|MockObject
     */
    protected $queue;

    /**
     * @var Configurations|MockObject
     */
    protected $config;

    /**
     * @var JsonSerializer|MockObject
     */
    protected $json;

    /**
     * @var Logger|MockObject
     */
    protected $logger;

    /**
     * @var TransactionObjectFactory|MockObject
     */
    protected $transactionObjectFactory;

    /**
     * @var TransactionObject|MockObject
     */
    protected $transactionObject;

    /**
     * @var OrderRepository|MockObject
     */
    protected $orderRepository;

    /**
     * @var Order|MockObject
     */
    protected $order;

    /**
     * @var Invoice|MockObject
     */
    protected $invoice;

    /**
     * @var InvoiceItem|MockObject
     */
    protected $invoiceItem;

    /**
     * @var Creditmemo|MockObject
     */
    protected $creditmemo;

    /**
     * @var CreditmemoItem|MockObject
     */
    protected $creditmemoItem;

    /**
     * @var CreditmemoCollection|MockObject
     */
    protected $creditmemoCollection;

    /**
     * @var InvoiceCollection|MockObject
     */
    protected $invoiceCollection;

    /**
     * @var PayloadItems|MockObject
     */
    protected $payloadItems;

    protected function setUp(): void
    {
        $this->queue = $this->getMockBuilder(Queue::class)
            ->onlyMethods(['getLastBatchId', 'save'])
            ->addMethods([
                'setBatchId',
                'setOrderId',
                'setBodyRequest',
                'setTransactionType',
                'setStatus',
                'setInvoiceId',
                'setCreditmemoId'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->queueFactory = $this->createMock(QueueFactory::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->order = $this->createMock(Order::class);
        $this->transactionObjectFactory = $this->createMock(TransactionObjectFactory::class);
        $this->transactionObject = $this->createMock(TransactionObject::class);
        $this->json = $this->createMock(JsonSerializer::class);
        $this->logger = $this->createMock(Logger::class);
        $this->config = $this->createMock(Configurations::class);
        $this->invoice = $this->createMock(Invoice::class);
        $this->invoiceCollection = $this->createMock(InvoiceCollection::class);
        $this->invoiceItem = $this->createMock(InvoiceItem::class);
        $this->creditmemo = $this->createMock(Creditmemo::class);
        $this->creditmemoCollection = $this->createMock(CreditmemoCollection::class);
        $this->creditmemoItem = $this->createMock(CreditmemoItem::class);
        $this->payloadItems = $this->createMock(PayloadItems::class);

        $this->queueHandler = new QueueHandler(
            $this->queueFactory,
            $this->orderRepository,
            $this->transactionObjectFactory,
            $this->json,
            $this->logger,
            $this->config,
            $this->payloadItems
        );
    }

    /**
     * Test createInvoiceQueue method
     */
    public function testCreateInvoiceQueue(): void
    {
        $this->order->expects($this->once())
            ->method('getInvoiceCollection')
            ->willReturn($this->invoiceCollection);

        $this->invoiceCollection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->invoice]));

        $this->invoice->expects($this->once())
            ->method('getData')
            ->with('zamp_transaction_id')
            ->willReturn(null);
        $this->invoice->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$this->invoiceItem]);
        $this->invoice->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->invoice->expects($this->once())
            ->method('getIncrementId')
            ->willReturn(100001);
        $this->invoice->expects($this->once())
            ->method('getShippingAmount')
            ->willReturn(10);
        $this->invoice->expects($this->once())
            ->method('getSubtotal')
            ->willReturn(10);
        $this->invoice->expects($this->once())
            ->method('getDiscountAmount')
            ->willReturn(2);
        $this->invoice->expects($this->once())
            ->method('getTaxAmount')
            ->willReturn(3);

        $this->transactionObjectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->transactionObject);

        $this->transactionObject->expects($this->once())
            ->method('createPayload')
            ->willReturnCallback(function () {
                return $this->transactionObject;
            });

        $this->transactionObject->expects($this->once())
            ->method('toArray')
            ->willReturn(['id' => '100001']);

        $this->json->expects($this->once())
            ->method('serialize')
            ->with(['id' => '100001'])
            ->willReturn('{"id":"100001"}');

        $this->order->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->testSaveQueue('invoice');
        $this->queueHandler->createInvoiceQueue($this->order);
    }

    /**
     * Test createCreditmemoQueue method
     */
    public function testCreateCreditmemoQueue(): void
    {
        $this->order->expects($this->once())
            ->method('getInvoiceCollection')
            ->willReturn($this->invoiceCollection);
        $this->invoiceCollection->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->invoice);
        $this->invoice->expects($this->once())
            ->method('getData')
            ->with('zamp_transaction_id')
            ->willReturn(1);

        $this->order->expects($this->once())
            ->method('getCreditmemosCollection')
            ->willReturn($this->creditmemoCollection);

        $this->creditmemoCollection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->creditmemo]));

        $this->creditmemo->expects($this->once())
            ->method('getData')
            ->with('zamp_transaction_id')
            ->willReturn(null);
        $this->creditmemo->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$this->creditmemoItem]);
        $this->creditmemo->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->creditmemo->expects($this->once())
            ->method('getIncrementId')
            ->willReturn(100001);
        $this->creditmemo->expects($this->once())
            ->method('getShippingAmount')
            ->willReturn(10);
        $this->creditmemo->expects($this->once())
            ->method('getSubtotal')
            ->willReturn(10);
        $this->creditmemo->expects($this->once())
            ->method('getDiscountAmount')
            ->willReturn(2);
        $this->creditmemo->expects($this->once())
            ->method('getTaxAmount')
            ->willReturn(3);

        $this->transactionObjectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->transactionObject);

        $this->transactionObject->expects($this->once())
            ->method('createPayload')
            ->willReturnCallback(function () {
                return $this->transactionObject;
            });

        $this->transactionObject->expects($this->once())
            ->method('toArray')
            ->willReturn(['id' => '100001']);

        $this->json->expects($this->once())
            ->method('serialize')
            ->with(['id' => '100001'])
            ->willReturn('{"id":"100001"}');

        $this->order->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->testSaveQueue('refund');
        $this->queueHandler->createCreditmemoQueue($this->order);
    }

    /**
     * Test saving of queue
     *
     * @param $type
     * @return void
     */
    protected function testSaveQueue($type): void
    {
        $this->queueFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->queue);
        $this->queue->expects($this->any())
            ->method('getLastBatchId')
            ->willReturn(1);
        $this->queue->expects($this->any())
            ->method('setBatchId')
            ->with(1)
            ->willReturnSelf();
        $this->queue->expects($this->any())
            ->method('setOrderId')
            ->with(1)
            ->willReturnSelf();
        $this->queue->expects($this->any())
            ->method('setBodyRequest')
            ->with('{"id":"100001"}')
            ->willReturnSelf();
        $this->queue->expects($this->any())
            ->method('setTransactionType')
            ->with($type)
            ->willReturnSelf();
        $this->queue->expects($this->any())
            ->method('setStatus')
            ->with('0')
            ->willReturnSelf();
        if ($type === 'invoice') {
                $this->queue->expects($this->any())
                ->method('setInvoiceId')
                ->with('1')
                ->willReturnSelf();
        } else {
            $this->queue->expects($this->any())
                ->method('setCreditmemoId')
                ->with('1')
                ->willReturnSelf();
        }
        $this->queue->expects($this->any())
            ->method('save')
            ->willReturnSelf();
    }
}
