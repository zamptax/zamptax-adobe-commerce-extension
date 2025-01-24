<?php declare(strict_types=1);

/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use ATF\Zamp\Services\UninstallData;
use ATF\Zamp\Services\UninstallSchema;
use ATF\Zamp\Services\DeleteLogger;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var UninstallData
     */
    private $uninstallData;

    /**
     * @var UninstallSchema
     */
    private $uninstallSchema;

    /**
     * @var DeleteLogger
     */
    private $deleteLogger;

    /**
     * Uninstall constructor.
     *
     * @param UninstallData $uninstallData
     * @param UninstallSchema $uninstallSchema
     * @param DeleteLogger $deleteLogger
     */
    public function __construct(
        UninstallData $uninstallData,
        UninstallSchema $uninstallSchema,
        DeleteLogger $deleteLogger
    ) {
        $this->uninstallData = $uninstallData;
        $this->uninstallSchema = $uninstallSchema;
        $this->deleteLogger = $deleteLogger;
    }

    /**
     * Invoked when the module is uninstalled with --remove-data option.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $this->uninstallData->uninstallCustomerAndProductAttribute();
        $this->uninstallData->removeConfigs();
        $this->uninstallSchema->removeTables();
        $this->uninstallSchema->dropTriggersForTables();
        $this->uninstallSchema->removeCoreTableColumns();

        $this->deleteLogger->execute();

        $setup->endSetup();
    }
}
