<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Block\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Block\Adminhtml\HistoricalTransaction\Queue;
use ATF\Zamp\Helper\Queue as QueueHelper;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue\Collection;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue\CollectionFactory;
use Magento\Backend\Block\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /**
     * @var QueueHelper|MockObject
     */
    protected $queueHelperMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var Queue
     */
    protected $block;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);
        $this->queueHelperMock = $this->createMock(QueueHelper::class);
        $this->collectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->block = new Queue($contextMock, $this->queueHelperMock, $this->collectionFactoryMock);
    }

    /**
     * Test for method getQueueTotal
     */
    public function testGetQueueTotal()
    {
        $collectionMock = $this->createMock(Collection::class);

        $this->collectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(10);

        $this->assertEquals(10, $this->block->getQueueTotal());
    }
}
