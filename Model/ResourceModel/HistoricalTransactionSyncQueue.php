<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class HistoricalTransactionSyncQueue extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'queue_zamp_historical_transaction_sync_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('queue_zamp_historical_transaction_sync', 'entity_id');
    }

    /**
     * Get last batch id
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLastBatchId()
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), new \Zend_Db_Expr('MAX(batch_id) as last_batch_id'));
        $lastBatchId = $this->getConnection()->fetchOne($select);

        return $lastBatchId ?: 0;
    }

    /**
     * Get current batch id in sync
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentBatchId()
    {
        $select = $this->getConnection()
            ->select()
            ->from(
                $this->getMainTable(),
                new \Zend_Db_Expr('MIN(batch_id) as current_batch_id')
            )
            ->where('status = ?', 0);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * Get total and synced orders count of current batch
     *
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentBatchInfo()
    {
        $currentBatchId = $this->getCurrentBatchId();
        if (!$currentBatchId) {
            return [];
        }

        $select = $this->getConnection()
            ->select()
            ->from(
                $this->getMainTable(),
                [
                    'total' => new \Zend_Db_Expr('count(*)'),
                    'synced' => new \Zend_Db_Expr('sum(case when status != 0 then 1 else 0 end)')
                ]
            )
            ->where('batch_id = ?', $currentBatchId);

        return $this->getConnection()->fetchRow($select);
    }

    /**
     * Is sync complete
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isSyncComplete()
    {
        $select = $this->getConnection()
            ->select()
            ->from(
                $this->getMainTable(),
                [
                    'total' => new \Zend_Db_Expr('count(*)'),
                    'pending' => new \Zend_Db_Expr('sum(case when status = 0 then 1 else 0 end)')
                ]
            );

        $totals = $this->getConnection()->fetchRow($select);
        return $totals['total'] > 0 && $totals['pending'] == 0;
    }
}
