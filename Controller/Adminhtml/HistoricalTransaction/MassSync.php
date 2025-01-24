<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Controller\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\System\Message\HistoricalTransactionSyncComplete;
use ATF\Zamp\Model\QueueHandler;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassSync extends Action implements HttpPostActionInterface
{
    /**
     * @var OrderCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var QueueHandler
     */
    protected $queueHandler;

    /**
     * @var FlagManager
     */
    protected $flagManager;

    /**
     * @param Context $context
     * @param OrderCollectionFactory $collectionFactory
     * @param Filter $filter
     * @param Configurations $config
     * @param QueueHandler $queueHandler
     * @param FlagManager $flagManager
     */
    public function __construct(
        Context $context,
        OrderCollectionFactory $collectionFactory,
        Filter $filter,
        Configurations $config,
        QueueHandler $queueHandler,
        FlagManager $flagManager
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->config = $config;
        $this->queueHandler = $queueHandler;
        $this->flagManager = $flagManager;
    }

    /**
     * Queue orders to sync
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();

        if (!$this->config->isModuleEnabled() || !$this->config->isSendTransactionsEnabled()) {
            return $redirect->setPath('*/*');
        }

        $items = $this->filter->getCollection($this->collectionFactory->create());
        foreach ($items as $item) {
            $this->queueHandler->createQueue($item->getId());
        }

        $this->resetSystemMessageFlag();

        return $redirect->setPath('*/*/queue');
    }

    /**
     * Reset system message flag as not dismissed
     *
     * @return void
     */
    protected function resetSystemMessageFlag()
    {
        if ($this->queueHandler->getTotalQueued() > 0) {
            $this->flagManager->saveFlag(HistoricalTransactionSyncComplete::FLAG_CODE_DISMISSED_MESSAGES, 0);
        }
    }
}
