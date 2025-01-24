<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Controller\Adminhtml\Transaction;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use ATF\Zamp\Model\Refund;
use ATF\Zamp\Logger\Logger;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class ResyncCreditMemo extends Action implements HttpGetActionInterface
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var Refund
     */
    protected $refund;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Action\Context $context
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param Refund $refund
     * @param Logger $logger
     */
    public function __construct(
        Action\Context $context,
        CreditmemoRepositoryInterface $creditmemoRepository,
        Refund $refund,
        Logger $logger
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->refund = $refund;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Manually sync credit memo transaction
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $creditMemoId = $this->getRequest()->getParam('creditmemo_id');
        $errorMessage = __('An error occurred while trying to sync this transaction.');

        try {
            $creditMemo = $this->creditmemoRepository->get($creditMemoId);

            $zampResponse = $this->refund->execute($creditMemo);
            if (isset($zampResponse['id'])) {
                $this->messageManager->addSuccessMessage(__('Credit memo successfully synced.'));
            } else {
                $this->messageManager->addErrorMessage($errorMessage);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($errorMessage);
            $this->logger->error(__('Error syncing transaction to Zamp: %1', $e->getMessage()));
        }

        return $this->_redirect('sales/creditmemo/view', ['creditmemo_id' => $creditMemoId]);
    }
}
