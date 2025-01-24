<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Model\System\Message;

use ATF\Zamp\Model\ResourceModel\HistoricalTransactionSyncQueue as QueueResource;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Notification\MessageInterface;

class HistoricalTransactionSyncComplete implements MessageInterface
{
    public const FLAG_CODE_DISMISSED_MESSAGES = 'atf_zamp_dismissed_messages';

    /**
     * @var QueueResource
     */
    protected $queueResource;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var FlagManager
     */
    protected $flagManager;

    /**
     * @param QueueResource $queueResource
     * @param AuthorizationInterface $authorization
     * @param UrlInterface $urlBuilder
     * @param FlagManager $flagManager
     */
    public function __construct(
        QueueResource $queueResource,
        AuthorizationInterface $authorization,
        UrlInterface $urlBuilder,
        FlagManager $flagManager
    ) {
        $this->queueResource = $queueResource;
        $this->authorization = $authorization;
        $this->urlBuilder = $urlBuilder;
        $this->flagManager = $flagManager;
    }

    /**
     * Retrieve unique message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return 'atf_zamp_sync_complete';
    }

    /**
     * Check whether message should be displayed
     *
     * @return bool
     */
    public function isDisplayed()
    {
        $isDismissed = $this->flagManager->getFlagData(self::FLAG_CODE_DISMISSED_MESSAGES) ?? false;
        return $this->authorization->isAllowed('ATF_Zamp::zamp')
            && $this->queueResource->isSyncComplete()
            && !$isDismissed;
    }

    /**
     * Retrieve message text
     *
     * @return string
     */
    public function getText()
    {
        $message = __(
            '<b>Zamp:</b> Historical transaction sync is complete.</b> ',
        );

        $message .= __(
            '<a href="%1">Dismiss message</a>.',
            $this->urlBuilder->getUrl('zamp/historicalTransaction/dismissMessage')
        );
        return $message;
    }

    /**
     * Retrieve message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_NOTICE;
    }
}
