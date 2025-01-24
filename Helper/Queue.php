<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Helper;

use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as QueueResourceModel;

class Queue
{
    public const PROGRESS_COMPLETE = 100;

    /**
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    /**
     * @param QueueResourceModel $queueResourceModel
     */
    public function __construct(
        QueueResourceModel $queueResourceModel
    ) {
        $this->queueResourceModel = $queueResourceModel;
    }

    /**
     * Get sync progress

     * @return float|int
     * @throws \Zend_Log_Exception
     */
    public function getQueueProgress()
    {
        $info = $this->queueResourceModel->getCurrentBatchInfo();

        if (empty($info)) {
            return self::PROGRESS_COMPLETE;
        }

        if (isset($info['synced'], $info['total'])) {
            $synced = (int) $info['synced'];
            $total = (int) $info['total'];
            if ($synced === 0) {
                return 1;
            } else {
                return round(($synced / $total) * 100);
            }
        }

        return self::PROGRESS_COMPLETE;
    }
}
