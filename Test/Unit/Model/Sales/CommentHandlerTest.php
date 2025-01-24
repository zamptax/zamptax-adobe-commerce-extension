<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model\Sales;

use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Sales\CommentHandler;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommentHandlerTest extends TestCase
{
    /**
     * @var CommentHandler|MockObject
     */
    private $commentHandler;

    /**
     * @var InvoiceRepositoryInterface|MockObject
     */
    private $invoiceRepository;

    /**
     * @var CreditmemoRepositoryInterface|MockObject
     */
    private $creditmemoRepository;

    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var Invoice|MockObject
     */
    private $invoice;

    /**
     * @var Creditmemo|MockObject
     */
    private $creditmemo;

    protected function setUp(): void
    {
        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->creditmemoRepository = $this->createMock(CreditmemoRepositoryInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->invoice = $this->createMock(Invoice::class);
        $this->creditmemo = $this->createMock(Creditmemo::class);

        $this->commentHandler = new CommentHandler(
            $this->invoiceRepository,
            $this->creditmemoRepository,
            $this->logger
        );
    }

    /**
     * Test addInvoiceComment method
     */
    public function testAddInvoiceComment(): void
    {
        $this->invoiceRepository->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($this->invoice);

        $this->invoice->expects($this->once())
            ->method('addComment')
            ->with(__('Transaction synced to Zamp with ID: %1', 1))
            ->willReturnSelf();

        $this->invoiceRepository->expects($this->once())
            ->method('save')
            ->with($this->invoice)
            ->willReturnSelf();

        $this->commentHandler->addInvoiceComment(1, 1);
    }

    /**
     * Test addCreditmemoComment method
     */
    public function testAddCreditmemoComment(): void
    {
        $this->creditmemoRepository->expects($this->once())
            ->method('get')
            ->with(2)
            ->willReturn($this->creditmemo);

        $this->creditmemo->expects($this->once())
            ->method('addComment')
            ->with(__('Transaction synced to Zamp with ID: %1', 2))
            ->willReturnSelf();

        $this->creditmemoRepository->expects($this->once())
            ->method('save')
            ->with($this->creditmemo)
            ->willReturnSelf();

        $this->commentHandler->addCreditmemoComment(2, 2);
    }
}
