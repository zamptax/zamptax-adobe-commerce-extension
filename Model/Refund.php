<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model;

use ATF\Zamp\Model\Service\Transaction;
use ATF\Zamp\Model\Transaction\PayloadItems;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo;

class Refund
{
    /**
     * @var TransactionObjectFactory
     */
    protected $transactionObjectFactory;

    /**
     * @var Transaction
     */
    protected $transactionService;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var PayloadItems
     */
    protected PayloadItems $payloadItems;

    /**
     * @var Sales\CommentHandler
     */
    protected $commentHandler;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @param TransactionObjectFactory $transactionObjectFactory
     * @param Transaction $transactionService
     * @param ResourceConnection $resource
     * @param PayloadItems $payloadItems
     * @param Sales\CommentHandler $commentHandler
     */
    public function __construct(
        TransactionObjectFactory $transactionObjectFactory,
        Transaction              $transactionService,
        ResourceConnection       $resource,
        PayloadItems             $payloadItems,
        Sales\CommentHandler     $commentHandler
    ) {
        $this->transactionObjectFactory = $transactionObjectFactory;
        $this->transactionService = $transactionService;
        $this->connection = $resource->getConnection();
        $this->payloadItems = $payloadItems;
        $this->commentHandler = $commentHandler;
        $this->resource = $resource;
    }

    /**
     * Process refund to zamp
     *
     * @param Creditmemo $creditMemo
     * @return array
     * @throws LocalizedException
     */
    public function execute(Creditmemo $creditMemo): array
    {
        $items = $this->payloadItems->execute($creditMemo->getAllItems());

        /** @var OrderInterface $order */
        $order = $creditMemo->getOrder();

        /**
         * Get first invoice item of the current order
         * @var InvoiceInterface $order
         */
        $invoice = $order->getInvoiceCollection()->getFirstItem();

        $request = new DataObject([
            'zamp_refund' => new DataObject([
                'id' => $creditMemo->getId(),
                'parent_id' => (int)$invoice->getId(),
                'increment_id' => $creditMemo->getIncrementId(),
                'customer_tax_exempt_code' => $order->getZampCustomerTaxExemptCode(),
                'updated_at' => date('Y-m-d H:i:s'),
                'shipping_amount' => (float)$creditMemo->getShippingAmount(),
                'sub_total' => $creditMemo->getSubTotal(),
                'discount_amount' => (float)$creditMemo->getDiscountAmount(),
                'tax_amount' => (float)$creditMemo->getTaxAmount(),
            ]),
            'zamp_items' => $items,
            'zamp_shipping_address' => $order->getShippingAddress() ?? $order->getBillingAddress()
        ]);

        /** @var TransactionObject $transactionObjModel */
        $transactionObjModel = $this->transactionObjectFactory->create();
        $transactionObj = $transactionObjModel->createPayload($request, 'refund');
        $zampResponse = $this->transactionService->createRefundTransaction($transactionObj->toArray());

        if (isset($zampResponse['code']) &&
            $zampResponse['code'] === Transaction::RESPONSE_CODE_CONFLICT
        ) {
            $zampResponse = $this->transactionService->retrieveTransaction($transactionObj->getId());
        }

        if (isset($zampResponse['id'])) {
            $this->saveZampTransId($zampResponse['id'], $creditMemo->getIncrementId());
            $this->commentHandler->addCreditmemoComment($creditMemo->getId(), $zampResponse['id']);
        }

        return $zampResponse;
    }

    /**
     * Save Zamp Transaction Id to sales credit memo tables
     *
     * @param string|int $id
     * @param string $incrementId
     * @return void
     */
    private function saveZampTransId(string|int $id = 0, string $incrementId = ''): void
    {
        foreach (['sales_creditmemo', 'sales_creditmemo_grid'] as $name) {
            $tableName = $this->resource->getTableName($name);
            $this->connection->update(
                $tableName,
                ['zamp_transaction_id' => $id],
                ['increment_id = ?' => $incrementId]
            );
        }
    }
}
