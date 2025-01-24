<?php declare(strict_types=1);

/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Setup\Patch\Data;

use Exception;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory;

class AddTaxExemptCodeCustomerAttribute implements DataPatchInterface
{
    public const CUSTOMER_TAX_EXEMPT_CODE = 'tax_exempt_code';

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private CustomerSetupFactory $customerSetupFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var SetFactory
     */
    private SetFactory $attributeSetFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param LoggerInterface $logger
     * @param SetFactory $attributeSetFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        LoggerInterface $logger,
        SetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->logger = $logger;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * Apply the patch to add the 'tax_exempt_code' customer attribute
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        try {
            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            /** @var $attributeSet Set */
            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $customerSetup->addAttribute(
                Customer::ENTITY,
                self::CUSTOMER_TAX_EXEMPT_CODE,
                [
                    'type' => 'varchar',
                    'label' => 'Tax Exempt Code',
                    'input' => 'text',
                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 999,
                    'system' => 0,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true,
                    'note' => __('To know the list of it, go to
                        <a target="_blank" href="https://developer.zamp.com/api/transactions">transactions API</a>
                        and see the entity object.'),
                ]
            );

            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, self::CUSTOMER_TAX_EXEMPT_CODE);

            $attribute->addData([
                'used_in_forms' => ['adminhtml_customer'],
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId
            ]);

            $attribute->save();
        } catch (LocalizedException|Exception $e) {
            $this->logger->error(__METHOD__);
            $this->logger->error($e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
