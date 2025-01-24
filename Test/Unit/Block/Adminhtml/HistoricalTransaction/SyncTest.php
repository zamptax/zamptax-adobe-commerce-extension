<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Block\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Block\Adminhtml\HistoricalTransaction\Sync;
use ATF\Zamp\Model\Configurations;
use Magento\Framework\UrlInterface;
use Magento\Backend\Block\Widget\Context;
use PHPUnit\Framework\TestCase;

class SyncTest extends TestCase
{
    /**
     * @var Sync
     */
    protected $block;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Configurations|MockObject
     */
    protected $configMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->urlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);
        $this->configMock = $this->createMock(Configurations::class);

        $this->contextMock
            ->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);

        $this->block = new Sync($this->contextMock, $this->configMock);
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
        $this->urlBuilderMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('zamp/historicalTransaction/massSync')
            ->willReturn('http://mass-sync-url');

        $this->block->getSyncUrl();
    }
}
