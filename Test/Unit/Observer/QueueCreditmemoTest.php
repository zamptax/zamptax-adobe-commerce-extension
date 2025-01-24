<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Observer;

use ATF\Zamp\Observer\QueueCreditmemo;
use ATF\Zamp\Model\QueueHandler;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueueCreditmemoTest extends TestCase
{
    /**
     * @var QueueCreditmemo
     */
    protected $queueCreditmemoObserver;

    /**
     * @var Observer
     */
    protected $observer;

    /**
     * @var Event
     */
    protected $event;

    /**
     * @var QueueHandler|MockObject
     */
    protected $queueHandler;

    /**
     * @var OrderRepository|MockObject
     */
    protected $orderRepository;

    /**
     * @var Order|MockObject
     */
    protected $order;

    protected function setUp(): void
    {
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->getMockBuilder(Event::class)
            ->onlyMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->queueHandler = $this->createMock(QueueHandler::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);

        $this->queueCreditmemoObserver = new QueueCreditmemo($this->queueHandler, $this->orderRepository);
    }

    /**
     * Test QueueCreditmemo observer
     */
    public function testExecute(): void
    {
        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->event->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($param) {
                if ($param === 'order_id') {
                    return 1;
                } elseif ($param === 'transaction_type') {
                    return 'invoice';
                }
                return null;
            });
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($this->order);
        $this->queueHandler->expects($this->once())
            ->method('createCreditmemoQueue')
            ->with($this->order)
            ->willReturn(null);
        $this->queueCreditmemoObserver->execute($this->observer);
    }
}
