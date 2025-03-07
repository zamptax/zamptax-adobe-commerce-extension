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

class Transact
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
        $this->payloadItems = $payloadItems;
        $this->connection = $resource->getConnection();
        $this->commentHandler = $commentHandler;
        $this->resource = $resource;
    }

    /**
     * Process invoice to zamp
     *
     * @param InvoiceInterface $invoice
     * @return array
     * @throws LocalizedException
     */
    public function execute(InvoiceInterface $invoice): array
    {
        /** @var OrderInterface $order */
        $order = $invoice->getOrder();

        $items = $this->payloadItems->execute($invoice->getAllItems());

        $request = new DataObject([
            'zamp_invoice' => new DataObject([
                'id' => $invoice->getId(),
                'increment_id' => $invoice->getIncrementId(),
                'customer_tax_exempt_code' => $order->getZampCustomerTaxExemptCode(),
                'updated_at' => $invoice->getCreatedAt(),
                'shipping_amount' => (float)$invoice->getShippingAmount(),
                'sub_total' => $invoice->getSubTotal(),
                'discount_amount' => abs((float)$invoice->getDiscountAmount()),
                'tax_amount' => (float)$invoice->getTaxAmount(),
            ]),
            'zamp_items' => $items,
            'zamp_shipping_address' => $order->getShippingAddress() ?? $order->getBillingAddress()
        ]);

        /** @var TransactionObject $transactionObjModel */
        $transactionObjModel = $this->transactionObjectFactory->create();
        $transactionObj = $transactionObjModel->createPayload($request, 'invoice');

        $zampResponse = $this->transactionService->createTransaction($transactionObj->toArray());

        if (isset($zampResponse['code']) &&
            $zampResponse['code'] === Transaction::RESPONSE_CODE_CONFLICT
        ) {
            $zampResponse = $this->transactionService->retrieveTransaction($transactionObj->getId());
        }

        if (isset($zampResponse['id'])) {
            $this->saveZampTransId($zampResponse['id'], $invoice->getIncrementId());
            $this->commentHandler->addInvoiceComment($invoice->getId(), $zampResponse['id']);
        }

        return $zampResponse;
    }

    /**
     * Save Zamp Transaction Id to sales invoice tables
     *
     * @param string|int $id
     * @param string $incrementId
     * @return void
     */
    private function saveZampTransId(string|int $id = 0, string $incrementId = ''): void
    {
        foreach (['sales_invoice', 'sales_invoice_grid'] as $name) {
            $tableName = $this->resource->getTableName($name);
            $this->connection->update(
                $tableName,
                ['zamp_transaction_id' => $id],
                ['increment_id = ?' => $incrementId]
            );
        }
    }
}
