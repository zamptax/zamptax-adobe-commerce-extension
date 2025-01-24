<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Block\Adminhtml\TransactionLog;

use ATF\Zamp\Model\ResourceModel\TransactionLog as TransactionLogResource;
use ATF\Zamp\Model\TransactionLog;
use ATF\Zamp\Model\TransactionLogFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class View extends Template
{
    /**
     * @var TransactionLogResource
     */
    protected $transactionLogResource;

    /**
     * @var TransactionLogFactory
     */
    protected $transactionLogFactory;

    /**
     * @param Context $context
     * @param TransactionLogResource $transactionLogResource
     * @param TransactionLogFactory $transactionLogFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        TransactionLogResource $transactionLogResource,
        TransactionLogFactory $transactionLogFactory,
        array $data = []
    ) {
        $this->transactionLogResource = $transactionLogResource;
        $this->transactionLogFactory = $transactionLogFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get transaction log data
     *
     * @return TransactionLog
     */
    public function getTransactionLogData()
    {
        $logId = $this->getRequest()->getParam('id');

        $log = $this->transactionLogFactory->create();

        $this->transactionLogResource->load($log, $logId);

        return $log;
    }

    /**
     * Get status label
     *
     * @param int $status
     * @return string
     */
    public function getStatusLabel($status)
    {
        $labels = TransactionLog::getStatusOptionArray();
        return $labels[$status] ?? '';
    }
}
