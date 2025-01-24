<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Observer;

use ATF\Zamp\Observer\SendTransactionToZamp;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\Transact;
use ATF\Zamp\Logger\Logger;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\TestCase;

/**
 * Provides tests for \ATF\Zamp\Observer\SendTransactionToZamp.
 *
 * @see SendTransactionToZamp
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 */
class SendTransactionToZampTest extends TestCase
{
    /**
     * @var Configurations
     */
    private $configMock;

    /**
     * @var Transact
     */
    private $transactMock;

    /**
     * @var Logger
     */
    private $loggerMock;

    /**
     * @var Observer
     */
    private $observerMock;

    /**
     * @var Event
     */
    private $eventMock;

    /**
     * @var SendTransactionToZamp
     */
    private $observerClass;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configurations::class);
        $this->transactMock = $this->createMock(Transact::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->observerMock = $this->createMock(Observer::class);
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerClass = new SendTransactionToZamp(
            $this->configMock,
            $this->transactMock,
            $this->loggerMock
        );
    }

    public function testExecuteSendsTransactionWhenConditionsAreMet()
    {
        // Mock the invoice object
        $invoiceMock = $this->getMockBuilder(Invoice::class)
            ->onlyMethods([
                'getOrder',
                'getIncrementId',
                'getShippingAmount',
                'getAllItems',
                'getDiscountAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        // Mock the configuration checks
        $this->configMock->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(true);

        $this->configMock->expects($this->once())
            ->method('isSendTransactionsEnabled')
            ->willReturn(true);

        // Mock the response from Zamp's API
        $zampResponse = ['id' => 'ZAMP123'];

        $this->transactMock->expects($this->once())
            ->method('execute')
            ->willReturn($zampResponse);

        // Mock the observer and event
        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn(new DataObject(['invoice' => $invoiceMock]));

        // Execute the observer
        $this->observerClass->execute($observerMock);
    }

    public function testExecuteDoesNotSendTransactionWhenModuleDisabled()
    {

        // Mock the configuration to disable the module
        $this->configMock->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(false);

        // Mock the invoice and event
        $invoiceMock = $this->createMock(Invoice::class);

        $this->observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn(new DataObject(['invoice' => $invoiceMock]));

        // Execute the observer
        $this->observerClass->execute($this->observerMock);
    }
}
