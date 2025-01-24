<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Model\System\Message;

use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as QueueResource;
use ATF\Zamp\Model\System\Message\HistoricalTransactionSyncComplete;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\AuthorizationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HistoricalTransactionSyncCompleteTest extends TestCase
{
    /**
     * @var HistoricalTransactionSyncComplete
     */
    protected $model;

    /**
     * @var QueueResource|MockObject
     */
    protected $queueResourceMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlMock;

    /**
     * @var AuthorizationInterface|MockObject
     */
    protected $authorizationMock;

    /**
     * @var FlagManager|MockObject
     */
    protected $flagManagerMock;

    protected function setUp(): void
    {
        $this->urlMock = $this->createMock(UrlInterface::class);
        $this->authorizationMock = $this->createMock(AuthorizationInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->queueResourceMock = $this->createMock(QueueResource::class);

        $this->model = new HistoricalTransactionSyncComplete(
            $this->queueResourceMock,
            $this->authorizationMock,
            $this->urlMock,
            $this->flagManagerMock
        );
    }

    /**
     * Test system message must display
     */
    public function testIsDisplayedTrue()
    {
        $this->flagManagerMock
            ->expects($this->once())
            ->method('getFlagData')
            ->with('atf_zamp_dismissed_messages')
            ->willReturn(false);

        $this->authorizationMock
            ->expects($this->once())
            ->method('isAllowed')
            ->with('ATF_Zamp::zamp')
            ->willReturn(true);
        $this->queueResourceMock
            ->expects($this->once())
            ->method('isSyncComplete')
            ->willReturn(true);

        $this->assertTrue($this->model->isDisplayed());
    }

    /**
     * Test system message must not display
     */
    public function testIsDisplayedFalse(): void
    {
        $this->flagManagerMock
            ->expects($this->once())
            ->method('getFlagData')
            ->with('atf_zamp_dismissed_messages')
            ->willReturn(true);

        $this->authorizationMock
            ->expects($this->once())
            ->method('isAllowed')
            ->with('ATF_Zamp::zamp')
            ->willReturn(true);
        $this->queueResourceMock
            ->expects($this->once())
            ->method('isSyncComplete')
            ->willReturn(true);

        $this->assertFalse($this->model->isDisplayed());
    }
}
