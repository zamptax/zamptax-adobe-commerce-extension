<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue;

use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as ResourceModel;
use ATF\Zamp\Model\HistoricalTransactionSyncQueue as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'queue_zamp_historical_transaction_sync_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
