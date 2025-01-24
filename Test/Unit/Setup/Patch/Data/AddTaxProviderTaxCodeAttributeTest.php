<?php declare(strict_types=1);

/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Setup\Patch\Data;

use ATF\Zamp\Setup\Patch\Data\AddTaxProviderTaxCodeAttribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Validator\ValidateException;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class AddTaxProviderTaxCodeAttributeTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $moduleDataSetupMock;

    /**
     * @var MockObject
     */
    private $eavSetupFactoryMock;

    /**
     * @var MockObject
     */
    private $eavSetupMock;

    /**
     * @var MockObject
     */
    private $loggerMock;

    /**
     * @var AddTaxProviderTaxCodeAttribute
     */
    private $patch;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupFactoryMock = $this->createMock(EavSetupFactory::class);
        $this->eavSetupMock = $this->createMock(EavSetup::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->eavSetupFactoryMock->method('create')->willReturn($this->eavSetupMock);

        $this->patch = new AddTaxProviderTaxCodeAttribute(
            $this->moduleDataSetupMock,
            $this->eavSetupFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * @return void
     */
    public function testApplySuccessfullyAddsAttribute(): void
    {
        $adapterInterface = $this->createMock(AdapterInterface::class);
        $this->moduleDataSetupMock->expects($this->any())->method('getConnection')
            ->willReturn($adapterInterface);
        $adapterInterface->expects($this->once())->method('startSetup');
        $adapterInterface->expects($this->once())->method('endSetup');

        $this->eavSetupMock->expects($this->once())
            ->method('addAttribute')
            ->with(
                Product::ENTITY,
                AddTaxProviderTaxCodeAttribute::PRODUCT_TAX_PROVIDER_TAX_CODE,
                [
                    'type' => 'varchar',
                    'label' => 'Tax Provider Tax Code',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 29,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'user_defined' => true,
                    'group' => 'General',
                    'visible_on_front' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => false,
                    'is_html_allowed_on_front' => false,
                    'used_in_product_listing' => true,
                    'apply_to' => '',
                    'note' => 'Click <a href="https://developer.zamp.com/api/tax-codes" target="_blank">here</a>
                        for the Tax Code reference'
                ]
            );

        $this->patch->apply();
    }

    /**
     * @return void
     */
    public function testApplyHandlesException(): void
    {
        $adapterInterface = $this->createMock(AdapterInterface::class);
        $this->moduleDataSetupMock->expects($this->any())->method('getConnection')
            ->willReturn($adapterInterface);
        $adapterInterface->expects($this->any())->method('startSetup');
        $adapterInterface->expects($this->any())->method('endSetup');

        $this->eavSetupMock->method('addAttribute')
            ->willThrowException(new LocalizedException(__('Error message')));

        $this->loggerMock->expects($this->exactly(2))
            ->method('error');

        $this->patch->apply();
    }
}
