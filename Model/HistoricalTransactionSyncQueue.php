<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model;

use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as ResourceModel;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class HistoricalTransactionSyncQueue extends AbstractModel
{
    public const STATUS_PENDING = '0';
    public const STATUS_SUCCESS = '1';
    public const STATUS_FAILED = '2';

    public const TRANSACTION_TYPE_INVOICE = 'invoice';
    public const TRANSACTION_TYPE_REFUND = 'refund';

    /**
     * @var string
     */
    protected $_eventPrefix = 'queue_zamp_historical_transaction_sync_model';

    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ResourceModel $resourceModel
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ResourceModel $resourceModel,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->resourceModel = $resourceModel;
    }

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get last batch id
     *
     * @return mixed
     */
    public function getLastBatchId()
    {
        return $this->resourceModel->getLastBatchId();
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
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_SUCCESS => __('Synced'),
            self::STATUS_FAILED => __('Error')
        ];
    }
}
