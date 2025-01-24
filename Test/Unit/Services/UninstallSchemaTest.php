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
use ATF\Zamp\Services\UninstallSchema;
use Zend_Db_Statement_Exception;
use Zend_Db_Statement_Interface;

class UninstallSchemaTest extends TestCase
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
     * @var UninstallSchema
     */
    private $uninstallSchema;

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

        $this->uninstallSchema = new UninstallSchema(
            $this->eavSetupFactoryMock,
            $this->moduleDataSetupMock
        );
    }

    /**
     * @return void
     */
    public function testRemoveTables(): void
    {
        $this->moduleDataSetupMock->method('getConnection')->willReturn($this->connectionMock);

        // Mock table existence check.
        $this->connectionMock->method('isTableExists')
            ->willReturn(true);

        // Expect dropTable to be called twice with the correct table names.
        $this->connectionMock->expects($this->exactly(2))
            ->method('dropTable')
            ->willReturn(true);

        // Call the method under test.
        $this->uninstallSchema->removeTables();
    }

    /**
     * @return void
     */
    public function testDropTriggersForTables(): void
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/rdebug.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(__METHOD__);
        $this->moduleDataSetupMock->method('getConnection')->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturnCallback(function (string $tableName) {
                return $tableName;
            });

        // Mock the result of the SHOW TRIGGERS LIKE query.
        $query1 = 'SHOW TRIGGERS LIKE \'queue_zamp_historical_transaction_sync\'';
        $query2 = 'SHOW TRIGGERS LIKE \'zamp_transaction_log\'';
        $triggersResult1 = [
            ['Trigger' => 'queue_zamp_historical_transaction_sync_1'],
            ['Trigger' => 'queue_zamp_historical_transaction_sync_2']
        ];
        $triggersResult2 = [
            ['Trigger' => 'zamp_transaction_log_1'],
            ['Trigger' => 'zamp_transaction_log_2']
        ];

        $this->connectionMock
            ->expects($this->exactly(6))
            ->method('query')
            ->willReturnCallback(function (string $query) use ($query1, $query2, $triggersResult1, $triggersResult2) {
                $result = match ($query) {
                    $query1 => $triggersResult1,
                    $query2 => $triggersResult2,
                    default => ''
                };

                return $this->createMockStatement($result);
            });

        $this->connectionMock->expects($this->any())
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries, $logger) {
                $executedQueries[] = $query;
            });

        // Call the method under test.
        $this->uninstallSchema->dropTriggersForTables();

        // Define the expected queries.
        $expectedQueries = [
            'SHOW TRIGGERS LIKE \'queue_zamp_historical_transaction_sync\'',
            'DROP TRIGGER IF EXISTS queue_zamp_historical_transaction_sync_1',
            'DROP TRIGGER IF EXISTS queue_zamp_historical_transaction_sync_2',
            'SHOW TRIGGERS LIKE \'zamp_transaction_log\'',
            'DROP TRIGGER IF EXISTS zamp_transaction_log_1',
            'DROP TRIGGER IF EXISTS zamp_transaction_log_2',
        ];

        // Verify that the queries were executed in the correct order.
        $this->assertEquals($expectedQueries, $executedQueries);
    }

    /**
     * Create a mock statement object.
     *
     * @param mixed $result
     * @return Zend_Db_Statement_Interface
     */
    private function createMockStatement(mixed $result): Zend_Db_Statement_Interface
    {
        $statementMock = $this->createMock(Zend_Db_Statement_Interface::class);

        if (is_array($result)) {
            // Return the result array for fetchAll if the result is an array.
            $statementMock->method('fetchAll')->willReturn($result);
        } else {
            // Return an empty array for fetchAll if the result is a query string.
            $statementMock->method('fetchAll')->willReturn([]);
        }

        return $statementMock;
    }

    public function testRemoveCoreTableColumns(): void
    {
        $this->moduleDataSetupMock->method('getConnection')->willReturn($this->connectionMock);
        // Define expected calls for isTableExists and tableColumnExists.
        $expectedTableChecks = [
            'sales_order' => true,
            'sales_invoice' => true,
            'sales_invoice_grid' => true,
            'sales_creditmemo' => true,
            'sales_creditmemo_grid' => true,
            'sales_order_item' => true,
            'sales_invoice_item' => true,
            'quote' => true
        ];

        $this->connectionMock->expects($this->exactly(8))
            ->method('getTableName')
            ->withConsecutive(
                ['sales_order'],
                ['sales_invoice'],
                ['sales_invoice_grid'],
                ['sales_creditmemo'],
                ['sales_creditmemo_grid'],
                ['sales_order_item'],
                ['sales_invoice_item'],
                ['quote']
            )->willReturnOnConsecutiveCalls(
                'sales_order',
                'sales_invoice',
                'sales_invoice_grid',
                'sales_creditmemo_grid',
                'sales_order_item',
                'sales_invoice_item',
                'quote'
            );

        // Mock table existence checks
        $this->connectionMock->expects($this->exactly(count($expectedTableChecks)))
            ->method('isTableExists')
            ->willReturn(true);

        // Mock column existence checks
        $this->connectionMock->expects($this->exactly(9))
            ->method('tableColumnExists')
            ->willReturn(true);

        // Define expected calls for dropColumn.
        $expectedDropCalls = [
            ['sales_order', 'zamp_customer_tax_exempt_code'],
            ['sales_order', 'is_zamp_tax_calculated'],
            ['sales_invoice', 'zamp_transaction_id'],
            ['sales_invoice_grid', 'zamp_transaction_id'],
            ['sales_creditmemo', 'zamp_transaction_id'],
            ['sales_creditmemo_grid', 'zamp_transaction_id'],
            ['sales_order_item', 'tax_provider_tax_code'],
            ['sales_invoice_item', 'tax_provider_tax_code'],
            ['quote', 'is_zamp_tax_calculated'],
        ];

        // Expect dropColumn to be called with the correct table and column names.
        $this->connectionMock
            ->expects($this->exactly(count($expectedDropCalls)))
            ->method('dropColumn');

        // Call the method under test.
        $this->uninstallSchema->removeCoreTableColumns();
    }
}
