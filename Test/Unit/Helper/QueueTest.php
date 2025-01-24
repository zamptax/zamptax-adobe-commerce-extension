<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Helper;

use ATF\Zamp\Helper\Queue as QueueHelper;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as QueueResourceModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /**
     * @var QueueHelper|MockObject
     */
    protected $queueHelper;

    /**
     * @var QueueResourceModel|MockObject
     */
    protected $queueResourceModelMock;

    protected function setUp(): void
    {
        $this->queueResourceModelMock = $this->createMock(QueueResourceModel::class);

        $this->queueHelper = new QueueHelper($this->queueResourceModelMock);
    }

    /**
     * Test for method getQueueProgress with empty queue
     */
    public function testGetQueueProgressWithEmptyQueue(): void
    {
        $this->queueResourceModelMock
            ->expects($this->once())
            ->method('getCurrentBatchInfo')
            ->willReturn([]);

        $this->assertEquals(100, $this->queueHelper->getQueueProgress());
    }

    /**
     * Test for method getQueueProgress with queue
     */
    public function testGetQueueProgressWithQueue(): void
    {
        $data = [
            'total' => 10,
            'synced' => 5
        ];

        $this->queueResourceModelMock
            ->expects($this->once())
            ->method('getCurrentBatchInfo')
            ->willReturn($data);

        $this->assertEquals(50, $this->queueHelper->getQueueProgress());
    }

    /**
     * Test for method getQueueProgress with queue but 0 synced
     */
    public function testGetQueueProgressWithQueueAndZeroSynced(): void
    {
        $data = [
            'total' => 10,
            'synced' => 0
        ];

        $this->queueResourceModelMock
            ->expects($this->once())
            ->method('getCurrentBatchInfo')
            ->willReturn($data);

        $this->assertEquals(1, $this->queueHelper->getQueueProgress());
    }
}
