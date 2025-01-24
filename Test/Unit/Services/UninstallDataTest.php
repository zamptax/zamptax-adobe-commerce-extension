<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Services;

use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use ATF\Zamp\Services\UninstallData;

class UninstallDataTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $eavSetupFactoryMock;

    /**
     * @var MockObject
     */
    private $moduleDataSetupMock;

    /**
     * @var MockObject
     */
    private $eavSetupMock;

    /**
     * @var AdapterInterface
     */
    private $connectionMock;

    /**
     * @var UninstallData
     */
    private $uninstallData;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->eavSetupFactoryMock = $this->createMock(EavSetupFactory::class);
        $this->moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupMock = $this->createMock(EavSetup::class);

        $this->eavSetupFactoryMock
            ->method('create')
            ->willReturn($this->eavSetupMock);

        $this->connectionMock = $this->createMock(AdapterInterface::class);

        $this->uninstallData = new UninstallData(
            $this->eavSetupFactoryMock,
            $this->moduleDataSetupMock
        );
    }

    /**
     * @return void
     */
    public function testUninstallCustomerAndProductAttribute(): void
    {
        $this->moduleDataSetupMock->method('getConnection')->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->once())->method('startSetup');
        $this->connectionMock->expects($this->once())->method('endSetup');

        $this->eavSetupMock->expects($this->exactly(2))
            ->method('removeAttribute')->willReturnSelf();

        $this->connectionMock->expects($this->once())
            ->method('delete')
            ->with(
                $this->moduleDataSetupMock->getTable('patch_list'),
                ['patch_name IN (?)' => [
                    'ATF\Zamp\Setup\Patch\Data\AddTaxProviderTaxCodeAttribute',
                    'ATF\Zamp\Setup\Patch\Data\AddTaxExemptCodeCustomerAttribute'
                ]]
            );

        $this->uninstallData->uninstallCustomerAndProductAttribute();
    }

    /**
     * @return void
     */
    public function testRemoveConfigs(): void
    {
        $this->moduleDataSetupMock->method('getConnection')->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->once())
            ->method('delete')
            ->with(
                $this->moduleDataSetupMock->getTable('core_config_data'),
                ['path LIKE ?' => '%/zamp_configuration/%']
            );

        $this->uninstallData->removeConfigs();
    }
}
