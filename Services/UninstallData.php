<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Services;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetupFactory;
use ATF\Zamp\Setup\Patch\Data\AddTaxProviderTaxCodeAttribute;
use ATF\Zamp\Setup\Patch\Data\AddTaxExemptCodeCustomerAttribute;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UninstallData
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Uninstall constructor.
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Invoked when the module is uninstalled with --remove-data option.
     *
     * @return void
     */
    public function uninstallCustomerAndProductAttribute(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(
            Product::ENTITY,
            AddTaxProviderTaxCodeAttribute::PRODUCT_TAX_PROVIDER_TAX_CODE
        );
        $eavSetup->removeAttribute(
            Customer::ENTITY,
            AddTaxExemptCodeCustomerAttribute::CUSTOMER_TAX_EXEMPT_CODE
        );

        // force string as value will be checked as string
        // phpcs:disable Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        $patchNames = [
            'ATF\Zamp\Setup\Patch\Data\AddTaxProviderTaxCodeAttribute',
            'ATF\Zamp\Setup\Patch\Data\AddTaxExemptCodeCustomerAttribute'
        ];

        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable('patch_list'),
            ['patch_name IN (?)' => $patchNames]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Remove module config records from core_config_data table.
     *
     * @return void
     */
    public function removeConfigs(): void
    {
        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['path LIKE ?' => '%/zamp_configuration/%']
        );
    }
}
