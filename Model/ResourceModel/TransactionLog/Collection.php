<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\ResourceModel\TransactionLog;

use ATF\Zamp\Model\TransactionLog;
use ATF\Zamp\Model\ResourceModel\TransactionLog as TransactionLogResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            TransactionLog::class,
            TransactionLogResourceModel::class
        );
    }
}
