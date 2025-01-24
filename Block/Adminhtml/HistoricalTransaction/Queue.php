<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Block\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Helper\Queue as QueueHelper;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue\CollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Queue extends Template
{
    public const PROGRESS_COMPLETE = 100;

    /**
     * @var QueueHelper
     */
    protected $queueHelper;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param QueueHelper $queueHelper
     * @param CollectionFactory $collectionFactory
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Context $context,
        QueueHelper $queueHelper,
        CollectionFactory $collectionFactory,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->queueHelper = $queueHelper;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get sync progress
     *
     * @return float|int
     */
    public function getQueueProgress()
    {
        return $this->queueHelper->getQueueProgress();
    }

    /**
     * Get collection size
     *
     * @return int
     */
    public function getQueueTotal()
    {
        $collection = $this->collectionFactory->create();
        return $collection->getSize();
    }
}
