<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Cron;

use ATF\Zamp\Cron\ProcessQueue;
use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\HistoricalTransactionSyncQueue;
use ATF\Zamp\Model\HistoricalTransactionSyncQueueFactory as QueueFactory;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as QueueResourceModel;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue\Collection;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue\CollectionFactory as QueueCollectionFactory;
use ATF\Zamp\Model\Sales\CommentHandler;
use ATF\Zamp\Model\Service\Transaction as TransactionService;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test ProcessQueue class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessQueueTest extends TestCase
{
    /**
     * @var ProcessQueue
     */
    protected $cron;

    /**
     * @var QueueFactory|MockObject
     */
    protected $queueFactory;

    /**
     * @var QueueResourceModel|MockObject
     */
    protected $queueResourceModel;

    /**
     * @var QueueCollectionFactory|MockObject
     */
    protected $queueCollectionFactory;

    /**
     * @var Configurations|MockObject
     */
    protected $config;

    /**
     * @var TransactionService|MockObject
     */
    protected $transactionService;

    /**
     * @var JsonSerializer|MockObject
     */
    protected $json;

    /**
     * @var Logger|MockObject
     */

    protected $logger;
    /**
     * @var EventManager|MockObject
     */
    protected $eventManager;

    /**
     * @var Collection|MockObject
     */
    protected $collectionMock;

    /**
     * @var CommentHandler|MockObject
     */
    protected $commentHandler;

    protected function setUp(): void
    {
        $this->queueFactory = $this->createMock(QueueFactory::class);
        $this->queueResourceModel = $this->createMock(QueueResourceModel::class);
        $this->queueCollectionFactory = $this->createPartialMock(
            QueueCollectionFactory::class,
            ['create']
        );

        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['getIterator','addFieldToFilter','setPageSize','setCurPage'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->createMock(Configurations::class);
        $this->transactionService = $this->createMock(TransactionService::class);
        $this->json = $this->createMock(JsonSerializer::class);
        $this->logger = $this->createMock(Logger::class);
        $this->eventManager =  $this->createMock(EventManager::class);
        $this->commentHandler = $this->createMock(CommentHandler::class);

        $this->cron = new ProcessQueue(
            $this->queueFactory,
            $this->queueResourceModel,
            $this->queueCollectionFactory,
            $this->config,
            $this->transactionService,
            $this->json,
            $this->logger,
            $this->eventManager,
            $this->commentHandler
        );
    }

    /**
     * Test sync cron
     *
     * @dataProvider providerForSync()
     */
    public function testSync($data): void
    {
        $this->config
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(true);
        $this->config
            ->expects($this->once())
            ->method('isSendTransactionsEnabled')
            ->willReturn(true);

        $this->queueResourceModel->expects($this->once())->method('getCurrentBatchId')->willReturn(3);

        $queue = [];
        foreach ($data as $item) {
            $row = $this->getMockBuilder(HistoricalTransactionSyncQueue::class)
                ->addMethods([
                    'getBodyRequest',
                    'getTransactionType',
                    'getInvoiceId',
                    'getOrderId',
                    'getCreditmemoId',
                    'setResponseData',
                    'setStatus'
                ])
                ->onlyMethods(['getId'])
                ->disableOriginalConstructor()
                ->getMock();

            $row->method('getBodyRequest')->willReturn($item['body_request']);
            $row->method('getTransactionType')->willReturn($item['transaction_type']);

            $queue[] = $row;
        };

        $this->collectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnCallback(function () {
                return $this->collectionMock;
            });
        $this->collectionMock->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->with(10)
            ->willReturnSelf();
        $this->collectionMock->expects($this->atLeastOnce())
            ->method('setCurPage')
            ->with(1)
            ->willReturnSelf();

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($queue));

        $this->queueCollectionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->json
            ->method('unserialize')
            ->willReturnCallback(function ($param) {
                return json_decode($param, true);
            });

        $this->transactionService
            ->expects($this->once())
            ->method('createTransaction')
            ->with(['id' => 1])
            ->willReturn(['id' => 1]);
        $this->transactionService
            ->expects($this->once())
            ->method('createRefundTransaction')
            ->with(['id' => 2])
            ->willReturn(['id' => 2]);

        $this->assertQueueUpdate($queue);
        $this->assertEntityUpdate();

        $this->cron->execute();
    }

    /**
     * Test for updating queue object and table
     */
    protected function assertQueueUpdate($queue): void
    {
        list($queueModel1, $queueModel2) = $queue;
        $this->json
            ->expects($this->exactly(2))
            ->method('serialize')
            ->willReturnCallback(function ($param) {
                 return json_encode($param);
            });

        $queueModel1
            ->expects($this->once())
            ->method('setResponseData')
            ->with('{"id":1}')
            ->willReturnSelf();
        $queueModel2
            ->expects($this->once())
            ->method('setResponseData')
            ->with('{"id":2}')
            ->willReturnSelf();
        $queueModel1
            ->expects($this->once())
            ->method('setStatus')
            ->with(1)
            ->willReturnSelf();
        $queueModel2
            ->expects($this->once())
            ->method('setStatus')
            ->with(1)
            ->willReturnSelf();
        $this->queueResourceModel
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($param) use ($queueModel1, $queueModel2) {
                static $index = 0;
                $expectedArgs = [$queueModel1, $queueModel2];
                $index++;
                if ($param === $expectedArgs[$index - 1]) {
                    return null;
                }
            });
        $queueModel1
            ->expects($this->exactly(4))
            ->method('getInvoiceId')
            ->willReturn(1);
        $queueModel1
            ->expects($this->once())
            ->method('getOrderId')
            ->willReturn(1);
        $queueModel2
            ->expects($this->exactly(3))
            ->method('getCreditmemoId')
            ->willReturn(2);
    }

    /**
     * Test for updating invoice and creditmemo tables
     */
    protected function assertEntityUpdate(): void
    {
        $connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->queueResourceModel
            ->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($connection);

        $connection->expects($this->exactly(4))
            ->method('getTableName')
            ->willReturnCallback(function ($param) {
                return $param;
            });

        $connection->expects($this->exactly(4))
            ->method('update')
            ->willReturnCallback(function (...$args) {
                static $index = 0;
                $expectedArgs = [
                    [
                        'sales_invoice',
                        ['zamp_transaction_id' => 1],
                        ['entity_id = ?' => 1]
                    ],
                    [
                        'sales_invoice_grid',
                        ['zamp_transaction_id' => 1],
                        ['entity_id = ?' => 1]
                    ],
                    [
                        'sales_creditmemo',
                        ['zamp_transaction_id' => 2],
                        ['entity_id = ?' => 2]
                    ],
                    [
                        'sales_creditmemo_grid',
                        ['zamp_transaction_id' => 2],
                        ['entity_id = ?' => 2]
                    ]
                ];
                $index++;
                if ($args === $expectedArgs[$index - 1]) {
                    return null;
                }
            });
        $this->eventManager
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                'zamp_transaction_sync_after',
                [
                    'transaction_id' => 1,
                    'transaction_type' => 'invoice',
                    'entity_id' => 1,
                    'order_id' => 1
                ]
            );

        $this->commentHandler->expects($this->once())
            ->method('addInvoiceComment')
            ->with(1, 1);

        $this->commentHandler->expects($this->once())
            ->method('addCreditmemoComment')
            ->with(2, 2);
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function providerForSync(): array
    {
        return [
            'base params' => [
                'queue' => [
                    [
                        'transaction_type' => 'invoice',
                        'body_request' => '{"id":"1"}',
                        'body_array' => ['id' => 1],
                    ],
                    [
                        'transaction_type' => 'refund',
                        'body_request' => '{"id":"2"}',
                        'body_array' => ['id' => 2],
                    ]
                ]
            ]
        ];
    }
}
