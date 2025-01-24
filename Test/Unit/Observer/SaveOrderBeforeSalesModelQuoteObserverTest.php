<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Observer;

use ATF\Zamp\Observer\SaveOrderBeforeSalesModelQuoteObserver;
use ATF\Zamp\Services\Quote as QuoteService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

class SaveOrderBeforeSalesModelQuoteObserverTest extends TestCase
{
    /**
     * @var SaveOrderBeforeSalesModelQuoteObserver
     */
    private $observer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->observer = new SaveOrderBeforeSalesModelQuoteObserver();
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $isZampCalculated = QuoteService::IS_ZAMP_CALCULATED;
        $quoteMock = $this->createMock(Quote::class);
        $orderMock = $this->createMock(Order::class);

        $quoteMock->expects($this->once())
            ->method('getData')
            ->with($isZampCalculated)
            ->willReturn(true);

        $orderMock->expects($this->once())
            ->method('setData')
            ->with($isZampCalculated, true);

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock],
            ]);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getEvent')
            ->willReturn($eventMock);

        $this->observer->execute($observerMock);
    }
}
