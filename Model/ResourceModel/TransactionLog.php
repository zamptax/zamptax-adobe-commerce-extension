<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TransactionLog extends AbstractDb
{
    /**
     * @var string Main table name
     */
    public const MAIN_TABLE = 'zamp_transaction_log';

    /**
     * @var string Main table primary key field name
     */
    public const ID_FIELD_NAME = 'log_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
