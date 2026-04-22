<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Observer;

use ATF\Zamp\Model\Service\TaxExemptCodeResolver;
use ATF\Zamp\Observer\SaveCustomerTaxExemptCode;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveCustomerTaxExemptCodeTest extends TestCase
{
    private TaxExemptCodeResolver|MockObject $taxExemptCodeResolver;

    private SaveCustomerTaxExemptCode $observerClass;

    private Order|MockObject $orderMock;

    private Observer $observerMock;

    private Event|MockObject $eventMock;

    protected function setUp(): void
    {
        $this->taxExemptCodeResolver = $this->createMock(TaxExemptCodeResolver::class);
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->onlyMethods(['getData', 'getCustomerId'])
            ->addMethods(['setZampCustomerTaxExemptCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock = $this->createMock(Observer::class);

        $this->observerClass = new SaveCustomerTaxExemptCode($this->taxExemptCodeResolver);
    }

    public function testExecuteSetsResolvedCodeForRegisteredCustomer(): void
    {
        $this->orderMock->method('getCustomerId')->willReturn('5');
        $this->taxExemptCodeResolver->expects($this->once())
            ->method('execute')
            ->with(5)
            ->willReturn('GOV_EDU');

        $this->orderMock->expects($this->once())
            ->method('setZampCustomerTaxExemptCode')
            ->with('GOV_EDU');

        $this->orderMock->expects($this->never())->method('getData');

        $this->eventMock->method('getOrder')->willReturn($this->orderMock);
        $this->observerMock->method('getEvent')->willReturn($this->eventMock);

        $this->observerClass->execute($this->observerMock);
    }

    public function testExecuteFallsBackToOrderCustomerTaxExemptCodeWhenResolverReturnsNull(): void
    {
        $this->orderMock->method('getCustomerId')->willReturn(null);
        $this->taxExemptCodeResolver->expects($this->once())
            ->method('execute')
            ->with(null)
            ->willReturn(null);

        $this->orderMock->expects($this->once())
            ->method('getData')
            ->with('customer_tax_exempt_code')
            ->willReturn('FALLBACK');

        $this->orderMock->expects($this->once())
            ->method('setZampCustomerTaxExemptCode')
            ->with('FALLBACK');

        $this->eventMock->method('getOrder')->willReturn($this->orderMock);
        $this->observerMock->method('getEvent')->willReturn($this->eventMock);

        $this->observerClass->execute($this->observerMock);
    }

    public function testExecuteDoesNotSetWhenNoCodeAvailable(): void
    {
        $this->orderMock->method('getCustomerId')->willReturn(null);
        $this->taxExemptCodeResolver->method('execute')->willReturn(null);
        $this->orderMock->method('getData')->willReturn(null);

        $this->orderMock->expects($this->never())->method('setZampCustomerTaxExemptCode');

        $this->eventMock->method('getOrder')->willReturn($this->orderMock);
        $this->observerMock->method('getEvent')->willReturn($this->eventMock);

        $this->observerClass->execute($this->observerMock);
    }
}
