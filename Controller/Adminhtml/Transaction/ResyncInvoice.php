<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Controller\Adminhtml\Transaction;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use ATF\Zamp\Model\Transact;
use ATF\Zamp\Logger\Logger;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class ResyncInvoice extends Action implements HttpGetActionInterface
{
    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var Transact
     */
    protected $transact;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Action\Context $context
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Transact $transact
     * @param Logger $logger
     */
    public function __construct(
        Action\Context $context,
        InvoiceRepositoryInterface $invoiceRepository,
        Transact $transact,
        Logger $logger
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->transact = $transact;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Manually sync invoice transaction
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        $errorMessage = __('An error occurred while trying to sync this transaction.');

        try {
            $invoice = $this->invoiceRepository->get($invoiceId);

            $zampResponse = $this->transact->execute($invoice);
            if (isset($zampResponse['id'])) {
                $this->messageManager->addSuccessMessage(__('Invoice successfully synced.'));
            } else {
                $this->messageManager->addErrorMessage($errorMessage);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($errorMessage);
            $this->logger->error(__('Error syncing transaction to Zamp: %1', $e->getMessage()));
        }

        return $this->_redirect('sales/invoice/view', ['invoice_id' => $invoiceId]);
    }
}
