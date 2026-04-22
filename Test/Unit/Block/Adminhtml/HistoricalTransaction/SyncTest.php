<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Block\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Block\Adminhtml\HistoricalTransaction\Sync;
use ATF\Zamp\Model\Configurations;
use PHPUnit\Framework\TestCase;

class SyncTest extends TestCase
{
    /**
     * @var Sync
     */
    protected $block;

    /**
     * @var Configurations|MockObject
     */
    protected $configMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configurations::class);
        $this->block = $this->getMockBuilder(Sync::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])
            ->getMock();

        $this->setProperty($this->block, 'config', $this->configMock);
    }

    /**
     * Test for method canSync
     */
    public function testCanSync(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(true);
        $this->configMock
            ->expects($this->once())
            ->method('isSendTransactionsEnabled')
            ->willReturn(true);

        $this->assertTrue($this->block->canSync());
    }

    /**
     * Test for method getSyncUrl
     */
    public function testGetSyncUrl()
    {
        $this->block
            ->expects($this->once())
            ->method('getUrl')
            ->with('zamp/historicalTransaction/massSync')
            ->willReturn('http://mass-sync-url');

        $this->assertSame('http://mass-sync-url', $this->block->getSyncUrl());
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setValue($object, $value);
    }
}
