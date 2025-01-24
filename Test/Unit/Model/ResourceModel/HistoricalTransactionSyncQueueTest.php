<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Model\ResourceModel;

use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HistoricalTransactionSyncQueueTest extends TestCase
{
    /**
     * @var HistoricalTransactionSyncQueue
     */
    protected $resourceModel;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Mysql|MockObject
     */
    protected $connectionMock;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var Select|MockObjec
     */
    protected $selectMock;
    
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->connectionMock = $this->getMockBuilder(Mysql::class)
            ->addMethods(['getMainTable'])
            ->onlyMethods(
                [
                    'select',
                    'update',
                    'fetchOne',
                    'fetchRow',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->context->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $this->selectMock = $this->getMockBuilder(Select::class)->disableOriginalConstructor()
            ->onlyMethods(['from', 'where'])
            ->getMock();

        $this->resourceModel = new HistoricalTransactionSyncQueue($this->context);
    }

    /**
     * Test getLastBatchId method
     */
    public function testGetLastBatchId(): void
    {
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->willReturn('queue_table');
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
            ->method('from')
            ->with('queue_table', new \Zend_Db_Expr('MAX(batch_id) as last_batch_id'))
            ->willReturnSelf();
        $this->connectionMock->expects($this->any())
            ->method('fetchOne')
            ->willReturn(10);

        $this->assertEquals(10, $this->resourceModel->getLastBatchId());
    }

    /**
     * Test getCurrentBatchId method
     */
    public function testGetCurrentBatchId(): void
    {
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->willReturn('queue_table');
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
            ->method('from')
            ->with('queue_table', new \Zend_Db_Expr('MIN(batch_id) as current_batch_id'))
            ->willReturnSelf();
        $this->selectMock->expects($this->any())
            ->method('where')
            ->with('status = ?', 0)
            ->willReturnSelf();
        $this->connectionMock->expects($this->any())
            ->method('fetchOne')
            ->willReturn(1);

        $this->assertEquals(1, $this->resourceModel->getCurrentBatchId());
    }

    /**
     * Test getCurrentBatchInfo method
     */
    public function testGetCurrentBatchInfo(): void
    {
        $expectedResult = ['total' => 100, 'synced' => 1];

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->willReturn('queue_table');
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->selectMock->expects($this->any())
            ->method('from')
            ->willReturnCallback(function () {
                return $this->selectMock;
            });

        $this->connectionMock->expects($this->any())
            ->method('fetchOne')
            ->willReturn(1);

        $this->selectMock->expects($this->any())
            ->method('where')
            ->willReturnCallback(function () {
                return $this->selectMock;
            });

        $this->connectionMock->expects($this->any())
            ->method('fetchRow')
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->resourceModel->getCurrentBatchInfo());
    }

    /**
     * Test isSyncComplete method
     */
    public function testIsSyncComplete(): void
    {
        $expectedResult = ['total' => 100, 'pending' => 50];

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->willReturn('queue_table');
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
            ->method('from')
            ->with('queue_table', [
                'total' => new \Zend_Db_Expr('count(*)'),
                'pending' => new \Zend_Db_Expr('sum(case when status = 0 then 1 else 0 end)')
            ])
            ->willReturnSelf();
        $this->connectionMock->expects($this->any())
            ->method('fetchRow')
            ->willReturn($expectedResult);

        $this->assertFalse($this->resourceModel->isSyncComplete());
    }
}
