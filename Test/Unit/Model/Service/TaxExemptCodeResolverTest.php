<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model\Service;

use ATF\Zamp\Model\Service\TaxExemptCodeResolver;
use ATF\Zamp\Setup\Patch\Data\AddTaxExemptCodeCustomerAttribute;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\GroupFactory;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\AttributeValue;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TaxExemptCodeResolverTest extends TestCase
{
    private CustomerRepositoryInterface|MockObject $customerRepository;

    private GroupFactory|MockObject $groupFactory;

    private TaxExemptCodeResolver $resolver;

    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->groupFactory = $this->createMock(GroupFactory::class);
        $this->resolver = new TaxExemptCodeResolver($this->customerRepository, $this->groupFactory);
    }

    public function testExecuteReturnsNullWithoutCustomerId(): void
    {
        $this->customerRepository->expects($this->never())->method('getById');
        $this->assertNull($this->resolver->execute(null));
        $this->assertNull($this->resolver->execute(0));
    }

    public function testExecuteReturnsCustomerCodeWhenSet(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getCustomAttribute')
            ->with(AddTaxExemptCodeCustomerAttribute::CUSTOMER_TAX_EXEMPT_CODE)
            ->willReturn(new AttributeValue([
                AttributeInterface::ATTRIBUTE_CODE => AddTaxExemptCodeCustomerAttribute::CUSTOMER_TAX_EXEMPT_CODE,
                AttributeInterface::VALUE => 'SPECIAL_1',
            ]));
        $customer->expects($this->never())->method('getGroupId');

        $this->customerRepository->method('getById')->with(10)->willReturn($customer);

        $this->assertSame('SPECIAL_1', $this->resolver->execute(10));
    }

    public function testExecuteReturnsGroupCodeWhenCustomerAttributeEmpty(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getCustomAttribute')
            ->with(AddTaxExemptCodeCustomerAttribute::CUSTOMER_TAX_EXEMPT_CODE)
            ->willReturn(null);
        $customer->method('getGroupId')->willReturn(3);

        $group = $this->getMockBuilder(Group::class)
            ->onlyMethods(['load', 'getId', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $group->method('load')->with(3)->willReturnSelf();
        $group->method('getId')->willReturn('3');
        $group->method('getData')->with('zamp_tax_exempt_code')->willReturn('GOV_EDU');

        $this->groupFactory->method('create')->willReturn($group);
        $this->customerRepository->method('getById')->with(20)->willReturn($customer);

        $this->assertSame('GOV_EDU', $this->resolver->execute(20));
    }

    public function testExecuteReturnsNullWhenNoSuchCustomer(): void
    {
        $this->customerRepository->method('getById')->willThrowException(
            new NoSuchEntityException(__('No such entity'))
        );
        $this->assertNull($this->resolver->execute(99));
    }
}
