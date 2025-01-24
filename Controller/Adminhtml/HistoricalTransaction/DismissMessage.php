<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Controller\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Model\System\Message\HistoricalTransactionSyncComplete;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\FlagManager;

class DismissMessage extends Action implements HttpGetActionInterface
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param Action\Context $context
     * @param FlagManager $flagManager
     */
    public function __construct(
        Action\Context $context,
        FlagManager $flagManager
    ) {
        parent::__construct($context);
        $this->flagManager = $flagManager;
    }

    /**
     * Dismiss system message
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $this->flagManager->saveFlag(HistoricalTransactionSyncComplete::FLAG_CODE_DISMISSED_MESSAGES, 1);

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
