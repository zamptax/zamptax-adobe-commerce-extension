<?php declare(strict_types=1);

/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Setup\Patch\Data;

use ATF\Zamp\Setup\Patch\Data\AddTaxExemptCodeCustomerAttribute;
use Exception;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Entity\Type;

/**
 * Separated the handle exception to fix the CouplingBetweenObjects issue
 */
class AddTaxExemptCodeCustomerAttributeTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $moduleDataSetupMock;

    /**
     * @var MockObject
     */
    private $customerSetupFactoryMock;

    /**
     * @var MockObject
     */
    private $loggerMock;

    /**
     * @var MockObject
     */
    private $attributeSetFactoryMock;

    /**
     * @var AddTaxExemptCodeCustomerAttribute
     */
    private $addTaxExemptCodeCustomerAttribute;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $this->customerSetupFactoryMock = $this->createMock(CustomerSetupFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->attributeSetFactoryMock = $this->createMock(SetFactory::class);

        $this->addTaxExemptCodeCustomerAttribute = new AddTaxExemptCodeCustomerAttribute(
            $this->moduleDataSetupMock,
            $this->customerSetupFactoryMock,
            $this->loggerMock,
            $this->attributeSetFactoryMock
        );
    }

    /**
     * @return void
     */
    public function testApplySuccess(): void
    {
        $connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->getMock();

        $this->moduleDataSetupMock->method('getConnection')
            ->willReturn($connectionMock);
        $connectionMock->expects($this->once())->method('startSetup');
        $connectionMock->expects($this->once())->method('endSetup');

        $customerSetupMock = $this->createMock(CustomerSetup::class);
        $this->customerSetupFactoryMock->method('create')
            ->willReturn($customerSetupMock);

        $eavConfig = $this->createMock(Config::class);
        $customerSetupMock->method('getEavConfig')
            ->willReturn($eavConfig);
        $type = $this->createMock(Type::class);
        $eavConfig->method('getEntityType')->willReturn($type);
        $type->method('getDefaultAttributeSetId')->willReturn('1');

        $attributeSetMock = $this->createMock(Set::class);
        $this->attributeSetFactoryMock->method('create')
            ->willReturn($attributeSetMock);
        $attributeSetMock->method('getDefaultGroupId')
            ->willReturn(1);

        $customerSetupMock->expects($this->once())
            ->method('addAttribute');

        $abstractAttribute = $this->createMock(AbstractAttribute::class);
        $eavConfig->method('getAttribute')->willReturn($abstractAttribute);
        $abstractAttribute->method('addData')->willReturnSelf();
        $abstractAttribute->method('save')->willReturnSelf();

        $this->addTaxExemptCodeCustomerAttribute->apply();
    }
}
