<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Sales;

use ATF\Zamp\Logger\Logger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class CommentHandler
{
    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param Logger $logger
     */
    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        Logger $logger
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->logger = $logger;
    }

    /**
     * Add comment to invoice
     *
     * @param int $invoiceId
     * @param string $zampId
     * @return void
     */
    public function addInvoiceComment($invoiceId, $zampId)
    {
        try {
            $invoice = $this->invoiceRepository->get($invoiceId);
            $invoice->addComment(
                __('Transaction synced to Zamp with ID: %1', $zampId)
            );
            $this->invoiceRepository->save($invoice);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Add comment to creditmemo
     *
     * @param int $creditmemoId
     * @param string $zampId
     * @return void
     */
    public function addCreditmemoComment($creditmemoId, $zampId)
    {
        try {
            $creditmemo = $this->creditmemoRepository->get($creditmemoId);
            $creditmemo->addComment(
                __('Transaction synced to Zamp with ID: %1', $zampId)
            );
            $this->creditmemoRepository->save($creditmemo);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
