<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Controller\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Helper\Queue as QueueHelper;
use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Configurations;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Status extends Action implements HttpGetActionInterface
{
    /**
     * @var QueueHelper
     */
    protected $queueHelper;

    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Action\Context $context
     * @param QueueHelper $queueHelper
     * @param Configurations $config
     * @param Logger $logger
     */
    public function __construct(
        Action\Context $context,
        QueueHelper $queueHelper,
        Configurations $config,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->queueHelper = $queueHelper;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Get sync progress
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultData = [];

        if (!$this->config->isModuleEnabled() || !$this->config->isSendTransactionsEnabled()) {
            $resultData['status'] = 'error';
            $resultData['message'] =  __('Historical Transaction sync is disabled.');
        }

        try {
            $progress = $this->queueHelper->getQueueProgress();
            $resultData['status'] = 'success';
            $resultData['progress'] = $progress;
        } catch (\Exception $e) {
            $resultData['status'] = 'error';
            $resultData['message'] = $e->getMessage();
            $this->logger->error($e->getMessage());
        }

        $resultJson->setData($resultData);

        return $resultJson;
    }
}
