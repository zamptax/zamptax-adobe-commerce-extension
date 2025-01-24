<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Observer;

use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\Refund;
use ATF\Zamp\Observer\SendRefundToZamp;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Creditmemo;

class SendRefundToZampTest extends TestCase
{
    /**
     * @var Configurations
     */
    private $configMock;

    /**
     * @var Refund
     */
    private $refundMock;

    /**
     * @var Logger
     */
    private $loggerMock;

    /**
     * @var SendRefundToZamp
     */
    private $observer;

    /**
     * @var Observer
     */
    private $observerMock;

    /**
     * @var Creditmemo
     */
    private $creditMemoMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configurations::class);
        $this->refundMock = $this->createMock(Refund::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->observerMock = $this->createMock(Observer::class);
        $this->creditMemoMock = $this->createMock(Creditmemo::class);

        // Initializing the observer with mocked dependencies
        $this->observer = new SendRefundToZamp(
            $this->configMock,
            $this->refundMock,
            $this->loggerMock
        );
    }

    public function testExecuteWithModuleDisabled(): void
    {
        // Simulate module disabled
        $this->configMock->method('isModuleEnabled')->willReturn(false);

        // Set up observer event to return the credit memo mock
        $this->observerMock->method('getEvent')->willReturn(
            new \Magento\Framework\DataObject(['creditmemo' => $this->creditMemoMock])
        );

        // Expect that sendRefundToZamp will not be called
        $this->refundMock->expects($this->never())->method('execute');

        // Execute the observer
        $this->observer->execute($this->observerMock);
    }

    public function testExecuteWithModuleEnabled(): void
    {
        // Simulate module and send transaction enabled
        $this->configMock->method('isModuleEnabled')->willReturn(true);
        $this->configMock->method('isSendTransactionsEnabled')->willReturn(true);

        // Set up observer event to return the credit memo mock
        $this->observerMock->method('getEvent')->willReturn(
            new DataObject(['creditmemo' => $this->creditMemoMock])
        );

        // Mock the sendRefundToZamp method
        $this->refundMock->expects($this->once())->method('execute');

        // Execute the observer
        $this->observer->execute($this->observerMock);
    }
}
