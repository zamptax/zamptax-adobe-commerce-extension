<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Cron;

use ATF\Zamp\Cron\CleanupLogs;
use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\TransactionLog;
use ATF\Zamp\Model\ResourceModel\TransactionLog\Collection;
use ATF\Zamp\Model\ResourceModel\TransactionLog as TransactionLogResource;
use ATF\Zamp\Model\ResourceModel\TransactionLog\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CleanupLogsTest extends TestCase
{
    /**
     * @var CleanupLogs
     */
    protected $cron;

    /**
     * @var Configurations|MockObject
     */
    protected $configMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var Collection|MockObject
     */
    protected $collectionMock;

    /**
     * @var TransactionLogResource|MockObject
     */
    protected $transactionLogResourceMock;

    /**
     * @var TransactionLog|MockObject
     */
    protected $transactionLogMock;

    /**
     * @var Logger|MockObject
     */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configurations::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $this->transactionLogMock = $this->createMock(TransactionLog::class);
        $this->transactionLogResourceMock = $this->createMock(TransactionLogResource::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->cron = new CleanupLogs(
            $this->configMock,
            $this->collectionFactoryMock,
            $this->transactionLogResourceMock,
            $this->loggerMock
        );
    }

    /**
     * Test log cleanup
     */
    public function testExecute(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('isLoggingEnabled')
            ->willReturn(true);
        $this->configMock
            ->expects($this->once())
            ->method('getLogLifetime')
            ->willReturn(30);

        $this->collectionMock
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->willReturnCallback(function () {
                return $this->collectionMock;
            });

        $this->collectionMock
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->collectionMock
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->transactionLogMock]));

        $this->collectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->transactionLogResourceMock
            ->expects($this->once())
            ->method('delete')
            ->with($this->transactionLogMock)
            ->willReturnSelf();

        $this->cron->execute();
    }
}
