<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Controller\Adminhtml\TransactionLog;

use ATF\Zamp\Model\ResourceModel\TransactionLog as TransactionLogResource;
use ATF\Zamp\Model\TransactionLogFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class View extends Action implements HttpGetActionInterface
{
    /** @var PageFactory  */
    protected $pageFactory;

    /**
     * @var TransactionLogResource
     */
    protected $transactionLogResource;

    /**
     * @var TransactionLogFactory
     */
    protected $transactionLogFactory;

    /**
     * @param Action\Context $context
     * @param PageFactory $pageFactory
     * @param TransactionLogResource $transactionLogResource
     * @param TransactionLogFactory $transactionLogFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $pageFactory,
        TransactionLogResource $transactionLogResource,
        TransactionLogFactory $transactionLogFactory,
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->transactionLogResource = $transactionLogResource;
        $this->transactionLogFactory = $transactionLogFactory;
    }

    /**
     * Execute
     *
     * @return Page|Redirect
     */
    public function execute(): Page|Redirect
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = $this->getRequest()->getParam('id', false);

        $model = $this->transactionLogFactory->create();
        $this->transactionLogResource->load($model, $id);

        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(__('This log no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Transaction Log %1', $model->getId()));

        return $page;
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        if (!$this->_authorization->isAllowed('ATF_Zamp::transaction_log')) {
            return  false;
        }

        return true;
    }
}
