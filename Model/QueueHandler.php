<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Model;

use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Transaction\PayloadItems;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class QueueHandler
{
    /**
     * @var HistoricalTransactionSyncQueueFactory
     */
    protected $historicalTransactionSyncQueueFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var TransactionObjectFactory
     */
    protected $transactionObjectFactory;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var PayloadItems
     */
    protected PayloadItems $payloadItems;

    /**
     * @var int
     */
    private $lastBatchId;

    /**
     * @var int
     */
    private $queued;

    /**
     * @param HistoricalTransactionSyncQueueFactory $historicalTransactionSyncQueueFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionObjectFactory $transactionObjectFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param Configurations $config
     * @param PayloadItems $payloadItems
     */
    public function __construct(
        HistoricalTransactionSyncQueueFactory $historicalTransactionSyncQueueFactory,
        OrderRepositoryInterface $orderRepository,
        TransactionObjectFactory $transactionObjectFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        Configurations $config,
        PayloadItems $payloadItems
    ) {
        $this->historicalTransactionSyncQueueFactory = $historicalTransactionSyncQueueFactory;
        $this->orderRepository = $orderRepository;
        $this->transactionObjectFactory = $transactionObjectFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->config = $config;
        $this->queued = 0;
        $this->payloadItems = $payloadItems;
    }

    /**
     * Create queue from invoices and creditmemos
     *
     * @param int $orderId
     * @return void|null
     */
    public function createQueue($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);

            if (!$this->isOrderValid($order)) {
                return;
            }
        } catch (NoSuchEntityException $e) {
            return;
        }

        $this->createInvoiceQueue($order);
        $this->createCreditmemoQueue($order);
    }

    /**
     * Create invoice queue
     *
     * @param OrderInterface $order
     * @return void
     */
    public function createInvoiceQueue($order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if (!$invoice->getData('zamp_transaction_id')) {
                $items = $this->payloadItems->execute($invoice->getAllItems());

                $request = new DataObject([
                    'zamp_invoice' => new DataObject([
                        'id' => $invoice->getId(),
                        'increment_id' => $invoice->getIncrementId(),
                        'customer_tax_exempt_code' => $order->getZampCustomerTaxExemptCode(),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'shipping_amount' => (float)$invoice->getShippingAmount(),
                        'sub_total' => (float) $invoice->getSubTotal(),
                        'discount_amount' => abs((float)$invoice->getDiscountAmount()),
                        'tax_amount' => (float) $invoice->getTaxAmount(),
                    ]),
                    'zamp_items' => $items,
                    'zamp_shipping_address' => $order->getShippingAddress() ?? $order->getBillingAddress(),
                ]);

                $serializedTransaction = $this->createTransactionRequest($request);
                if ($serializedTransaction) {
                    $this->saveQueue($order->getId(), $invoice->getId(), $serializedTransaction);
                }
            }
        }
    }

    /**
     * Create credit memo queue
     *
     * @param OrderInterface $order
     * @return void
     */
    public function createCreditmemoQueue($order)
    {
        $invoice = $order->getInvoiceCollection()->getFirstItem();

        if (!$invoice->getData('zamp_transaction_id')) {
            return;
        }

        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            if ($creditmemo->getData('zamp_transaction_id')) {
                continue;
            }

            $items = $this->payloadItems->execute($creditmemo->getAllItems());

            $request = new DataObject([
                'zamp_refund' => new DataObject([
                    'id' => $creditmemo->getId(),
                    'parent_id' => (int)$invoice->getId(),
                    'increment_id' => $creditmemo->getIncrementId(),
                    'customer_tax_exempt_code' => $order->getZampCustomerTaxExemptCode(),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'shipping_amount' => (float)$creditmemo->getShippingAmount(),
                    'sub_total' => (float) $creditmemo->getSubTotal(),
                    'discount_amount' => (float)$creditmemo->getDiscountAmount(),
                    'tax_amount' => (float)$creditmemo->getTaxAmount(),
                ]),
                'zamp_items' => $items,
                'zamp_shipping_address' => $order->getShippingAddress() ?? $order->getBillingAddress(),
            ]);

            $serializedTransaction = $this->createTransactionRequest(
                $request,
                HistoricalTransactionSyncQueue::TRANSACTION_TYPE_REFUND
            );

            if ($serializedTransaction) {
                $this->saveQueue(
                    $order->getId(),
                    $creditmemo->getId(),
                    $serializedTransaction,
                    HistoricalTransactionSyncQueue::TRANSACTION_TYPE_REFUND
                );
            }
        }
    }

    /**
     * Save queue
     *
     * @param int $orderId
     * @param int $referenceId
     * @param string $request
     * @param string $type
     * @return void
     */
    public function saveQueue($orderId, $referenceId, $request, $type = 'invoice')
    {
        $lastBatchId = $this->getLastBatchId();
        try {
            $queue = $this->historicalTransactionSyncQueueFactory->create();
            $queue
                ->setBatchId($lastBatchId)
                ->setOrderId($orderId)
                ->setBodyRequest($request)
                ->setTransactionType($type)
                ->setStatus(HistoricalTransactionSyncQueue::STATUS_PENDING);

            if ($type === HistoricalTransactionSyncQueue::TRANSACTION_TYPE_INVOICE) {
                $queue->setInvoiceId($referenceId);
            } else {
                $queue->setCreditmemoId($referenceId);
            }

            $queue->save();

            $this->queued++;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Create transaction object
     *
     * @param DataObject $request
     * @param string $type
     * @return bool|string
     */
    protected function createTransactionRequest($request, $type = 'invoice')
    {
        try {
            /** @var TransactionObject $transaction */
            $transaction = $this->transactionObjectFactory->create();
            $transaction->createPayload($request, $type);
            return $this->jsonSerializer->serialize($transaction->toArray());
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
        }

        return false;
    }

    /**
     * Get last batch id
     *
     * @return mixed
     */
    public function getLastBatchId()
    {
        if (null === $this->lastBatchId) {
            $queue = $this->historicalTransactionSyncQueueFactory->create();
            $lastBatchId = $queue->getLastBatchId();
            $this->lastBatchId = $lastBatchId + 1;
        }

        return $this->lastBatchId;
    }

    /**
     * Get total of ordered queued
     *
     * @return int
     */
    public function getTotalQueued()
    {
        return $this->queued;
    }

    /**
     * Is order valid for queueing
     *
     * @param OrderInterface $order
     * @return bool
     */
    private function isOrderValid($order)
    {
        $earliestDate = $this->config->getEarliestDateToSync();
        $minDate = $earliestDate->format('Y-m-d H:i:s');
        if ($order->getCreatedAt() < $minDate) {
            return false;
        }

        return true;
    }
}
