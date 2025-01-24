<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model;

use Magento\Framework\Model\AbstractModel;

class TransactionLog extends AbstractModel
{
    public const RESPONSE_STATUS_SUCCESS = 1;
    public const RESPONSE_STATUS_ERROR = 2;

    /**
     * @var string
     */
    protected $_eventPrefix = 'zamp_transaction_log';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\TransactionLog::class);
    }

    /**
     * Retrieve option array
     *
     * @return array
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function getStatusOptionArray(): array
    {
        return [
            self::RESPONSE_STATUS_SUCCESS => __('Success'),
            self::RESPONSE_STATUS_ERROR => __('Error')
        ];
    }
}
