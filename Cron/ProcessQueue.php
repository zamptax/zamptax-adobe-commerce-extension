<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Cron;

use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\HistoricalTransactionSyncQueue;
use ATF\Zamp\Model\HistoricalTransactionSyncQueueFactory as QueueFactory;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as QueueResourceModel;
use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue\CollectionFactory as QueueCollectionFactory;
use ATF\Zamp\Model\Sales\CommentHandler;
use ATF\Zamp\Model\Service\Transaction as TransactionService;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Process transactions in queue
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessQueue
{
    private const SIZE_PER_SYNC = 10;

    /**
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var QueueCollectionFactory
     */
    protected $queueCollectionFactory;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var JsonSerializer
     */
    protected $json;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var CommentHandler
     */
    protected $commentHandler;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param QueueFactory $queueFactory
     * @param QueueResourceModel $queueResourceModel
     * @param QueueCollectionFactory $queueCollectionFactory
     * @param Configurations $config
     * @param TransactionService $transactionService
     * @param JsonSerializer $json
     * @param Logger $logger
     * @param EventManager $eventManager
     * @param CommentHandler $commentHandler
     * @param ResourceConnection $resourceConnection
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        QueueFactory $queueFactory,
        QueueResourceModel $queueResourceModel,
        QueueCollectionFactory $queueCollectionFactory,
        Configurations $config,
        TransactionService $transactionService,
        JsonSerializer $json,
        Logger $logger,
        EventManager $eventManager,
        CommentHandler $commentHandler,
        ResourceConnection $resourceConnection
    ) {
        $this->queueFactory = $queueFactory;
        $this->queueResourceModel = $queueResourceModel;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->config = $config;
        $this->transactionService = $transactionService;
        $this->json = $json;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->commentHandler = $commentHandler;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Process queue of current batch
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if (!$this->config->isModuleEnabled() || !$this->config->isSendTransactionsEnabled()) {
            return;
        }

        $currentBatch = $this->queueResourceModel->getCurrentBatchId();

        $collection = $this->queueCollectionFactory->create();
        $collection
            ->addFieldToFilter('batch_id', $currentBatch)
            ->addFieldToFilter('status', HistoricalTransactionSyncQueue::STATUS_PENDING)
            ->setPageSize(self::SIZE_PER_SYNC)
            ->setCurPage(1);

        foreach ($collection as $item) {
            if ($item->getBodyRequest()) {
                $bodyRequest = $this->json->unserialize($item->getBodyRequest());

                if ($item->getTransactionType() === HistoricalTransactionSyncQueue::TRANSACTION_TYPE_INVOICE) {
                    $response = $this->transactionService->createTransaction($bodyRequest);
                } else {
                    $response = $this->transactionService->createRefundTransaction($bodyRequest);
                }

                if (isset($response['code']) &&
                    $response['code'] === TransactionService::RESPONSE_CODE_CONFLICT
                ) {
                    $response = $this->transactionService->retrieveTransaction($bodyRequest['id']);
                }

                $this->saveResponse($item, $response);
            }
        }
    }

    /**
     * Save response
     *
     * @param HistoricalTransactionSyncQueue $queue
     * @param array $response
     * @return void
     */
    protected function saveResponse($queue, $response)
    {
        $this->updateQueue($queue, $response);
        $this->saveTransactionId($queue, $response);
    }

    /**
     * Update queue with response data and status
     *
     * @param HistoricalTransactionSyncQueue $queue
     * @param array $response
     * @return void
     */
    protected function updateQueue($queue, $response)
    {
        if (isset($response['id'])) {
            $status = HistoricalTransactionSyncQueue::STATUS_SUCCESS;
        } else {
            $status = HistoricalTransactionSyncQueue::STATUS_FAILED;
        }

        try {
            $responseSerialized = $this->json->serialize($response);
        } catch (\InvalidArgumentException $e) {
            $responseSerialized = $e->getMessage();
            $this->logger->info($e->getMessage());
        }

        try {
            $queue
                ->setResponseData($responseSerialized)
                ->setStatus($status);
            $this->queueResourceModel->save($queue);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Save transaction ID to invoice or creditmemo
     *
     * @param HistoricalTransactionSyncQueue $queue
     * @param array $response
     * @return void
     */
    protected function saveTransactionId($queue, $response)
    {
        if (!isset($response['id'])) {
            return;
        }

        $connection = $this->queueResourceModel->getConnection();
        if ($queue->getTransactionType() === HistoricalTransactionSyncQueue::TRANSACTION_TYPE_INVOICE) {
            foreach (['sales_invoice', 'sales_invoice_grid'] as $name) {
                $tableName = $this->resourceConnection->getTableName($name);
                $connection->update(
                    $tableName,
                    ['zamp_transaction_id' => $response['id']],
                    ['entity_id = ?' => $queue->getInvoiceId()]
                );
            }

            $this->eventManager->dispatch(
                'zamp_transaction_sync_after',
                [
                    'transaction_id' => $response['id'],
                    'transaction_type' => 'invoice',
                    'entity_id' => $queue->getInvoiceId(),
                    'order_id' => $queue->getOrderId()
                ]
            );

            $this->commentHandler->addInvoiceComment($queue->getInvoiceId(), $response['id']);
        } else {
            foreach (['sales_creditmemo', 'sales_creditmemo_grid'] as $name) {
                $tableName = $this->resourceConnection->getTableName($name);
                $connection->update(
                    $tableName,
                    ['zamp_transaction_id' => $response['id']],
                    ['entity_id = ?' => $queue->getCreditmemoId()]
                );
            }

            $this->commentHandler->addCreditmemoComment($queue->getCreditmemoId(), $response['id']);
        }
    }
}
