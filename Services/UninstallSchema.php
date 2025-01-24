<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Services;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UninstallSchema
{
    /**
     * Table names associated with the module
     *
     * Ensure to declare tables that reference other tables before declaring the tables they are referencing
     * This order helps prevent the "a foreign key constraint fails" error
     *
     * @var array
     */
    protected array $tableNames = [
        'queue_zamp_historical_transaction_sync',
        'zamp_transaction_log'
    ];

    /**
     * Maps columns and tables from Magento core tables to those created by the module
     *
     * @var array
     */
    protected array $coreTableColumnMappings = [
        'sales_order' => [
            'zamp_customer_tax_exempt_code',
            'is_zamp_tax_calculated'
        ],
        'sales_invoice' => [
            'zamp_transaction_id'
        ],
        'sales_invoice_grid' => [
            'zamp_transaction_id',
        ],
        'sales_creditmemo' => [
            'zamp_transaction_id'
        ],
        'sales_creditmemo_grid' => [
            'zamp_transaction_id'
        ],
        'sales_order_item' => [
            'tax_provider_tax_code'
        ],
        'sales_invoice_item' => [
            'tax_provider_tax_code'
        ],
        'quote' => [
            'is_zamp_tax_calculated'
        ]
    ];

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
     * Removes tables associated with the module.
     *
     * @return void
     */
    public function removeTables(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        foreach ($this->tableNames as $tableName) {
            // Get the fully qualified table name with prefix and suffix
            $tableName = $connection->getTableName($tableName);
            if ($connection->isTableExists($tableName)) {
                $connection->dropTable($tableName);
            }
        }
    }

    /**
     * Drops triggers associated with the module's tables.
     *
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function dropTriggersForTables(): void
    {
        foreach ($this->tableNames as $tableName) {
            $this->dropTriggers($this->moduleDataSetup->getConnection()->getTableName($tableName));
        }
    }

    /**
     * Drops all triggers for a given table.
     *
     * @param string $tableName
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function dropTriggers(string $tableName): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $triggers = $connection->query('SHOW TRIGGERS LIKE \'' . $tableName . '\'')->fetchAll();

        if (!$triggers) {
            return;
        }

        foreach ($triggers as $trigger) {
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
            $connection->query('DROP TRIGGER IF EXISTS ' . $trigger['Trigger']);
        }
    }

    /**
     * Remove columns from Magento core tables that were created by the module.
     *
     * @return void
     */
    public function removeCoreTableColumns(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        foreach ($this->coreTableColumnMappings as $tableName => $columnNames) {
            // Get the fully qualified table name with prefix and suffix
            $tableName = $connection->getTableName($tableName);
            if (!$connection->isTableExists($tableName)) {
                continue;
            }
            foreach ($columnNames as $columnName) {
                if (!$connection->tableColumnExists($tableName, $columnName)) {
                    continue;
                }
                $connection->dropColumn($tableName, $columnName);
            }
        }
    }
}
