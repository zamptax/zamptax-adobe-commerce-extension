<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Model\ResourceModel\Order\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface as Logger;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as QueueResourceModel;

class Collection extends SearchResult
{
    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param Configurations $config
     * @param QueueResourceModel $queueResourceModel
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        Configurations $config,
        QueueResourceModel $queueResourceModel,
        $mainTable = 'sales_order_grid',
        $resourceModel = Order::class
    ) {
        $this->config = $config;
        $this->queueResourceModel = $queueResourceModel;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @inheritDoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $earliestDate = $this->config->getEarliestDateToSync();
        $this->addFieldToFilter('main_table.created_at', ['gteq' => $earliestDate->format('Y-m-d H:i:s')]);

        $this->getSelect()
            ->join(
                ['si' => $this->getTable('sales_invoice')],
                'main_table.entity_id = si.order_id',
                []
            )
            ->joinLeft(
                ['sc' => $this->getTable('sales_creditmemo')],
                'main_table.entity_id = sc.order_id',
                []
            )
            ->joinLeft(
                ['queue' => $this->queueResourceModel->getMainTable()],
                'main_table.entity_id = queue.order_id',
                []
            )
            ->where('si.zamp_transaction_id IS NULL OR (sc.zamp_transaction_id IS NULL AND sc.order_id IS NOT NULL)')
            ->where('queue.order_id IS NULL OR 
            (sc.order_id IS NOT NULL AND sc.zamp_transaction_id IS NULL AND si.zamp_transaction_id IS NOT NULL)')
            ->group('main_table.entity_id');

        return $this;
    }
}
