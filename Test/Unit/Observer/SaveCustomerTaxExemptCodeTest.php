<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Observer;

use ATF\Zamp\Observer\SaveCustomerTaxExemptCode;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;
use Magento\Customer\Api\Data\CustomerInterface;
use PHPUnit\Framework\TestCase;

class SaveCustomerTaxExemptCodeTest extends TestCase
{
    /**
     * @var Session
     */
    private $customerSessionMock;

    /**
     * @var CustomerInterface
     */
    private $customerMock;

    /**
     * @var Order
     */
    private $orderMock;

    /**
     * @var SaveCustomerTaxExemptCode
     */
    private $observerClass;

    /**
     * @var Observer
     */
    private $observerMock;

    /**
     * @var Event
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->customerSessionMock = $this->createMock(Session::class);

        $this->customerMock = $this->getMockForAbstractClass(
            CustomerInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getTaxExemptCode']
        );

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->onlyMethods(['getData'])
            ->addMethods(['setZampCustomerTaxExemptCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock = $this->createMock(Observer::class);

        $this->observerClass = new SaveCustomerTaxExemptCode($this->customerSessionMock);
    }

    public function testExecuteSetsTaxExemptCodeWhenLoggedIn()
    {
        $taxExemptCode = 'EXEMPT123';

        // Mock the customer session and customer with a tax exempt code
        $this->customerMock
            ->expects($this->exactly(2))
            ->method('getTaxExemptCode')
            ->willReturn($taxExemptCode);

        $this->customerSessionMock
            ->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock
            ->expects($this->exactly(2))
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        // Mock the order object from the observer
        $this->orderMock
            ->expects($this->once())
            ->method('setZampCustomerTaxExemptCode')
            ->with($taxExemptCode);

        // Mock the observer and event
        $this->eventMock
            ->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->observerMock
            ->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        // Execute the observer
        $this->observerClass->execute($this->observerMock);
    }

    public function testExecuteDoesNotSetTaxExemptCodeWhenNotLoggedIn()
    {
        // Customer is not logged in
        $this->customerSessionMock
            ->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

        // Mock the observer and event
        $this->eventMock
            ->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        // Mock the observer
        $this->observerMock
            ->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        // Execute the observer
        $this->observerClass->execute($this->observerMock);
    }

    public function testExecuteDoesNotSetTaxExemptCodeWhenCustomerHasNoExemptCode()
    {
        // Mock the customer session, logged in but no tax exempt code
        $this->customerMock
            ->expects($this->once())
            ->method('getTaxExemptCode')
            ->willReturn(null);

        $this->customerSessionMock
            ->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock
            ->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        // Mock the order object from the observer
        $taxExemptCode = 'EXEMPT123';
        $this->orderMock
            ->expects($this->once())
            ->method('getData')
            ->with('customer_tax_exempt_code')
            ->willReturn($taxExemptCode);

        // Mock the observer and event
        $this->eventMock
            ->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        // Mock the observer
        $this->observerMock
            ->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        // Execute the observer
        $this->observerClass->execute($this->observerMock);
    }
}
