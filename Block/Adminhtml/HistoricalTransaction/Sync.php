<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Block\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Model\Configurations;
use Magento\Backend\Block\Widget\Context;

class Sync extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @param Context $context
     * @param Configurations $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Configurations $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * Prepare button
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $searchButtonProps = [
            'id' => 'transaction_search',
            'label' => __('Search'),
            'class' => 'primary transaction-search',
            'button_class' => '',
        ];
        $this->buttonList->add('transaction_search', $searchButtonProps);

        $syncButtonProps = [
            'id' => 'transaction_sync',
            'label' => __('Sync'),
            'class' => 'secondary transaction-sync',
            'button_class' => '',
            'disabled' => !$this->canSync(),
        ];
        $this->buttonList->add('transaction_sync', $syncButtonProps);

        return parent::_prepareLayout();
    }

    /**
     * Can sync
     *
     * @return bool
     */
    public function canSync()
    {
        return $this->config->isModuleEnabled() && $this->config->isSendTransactionsEnabled();
    }

    /**
     * Get sync url
     *
     * @return string
     */
    public function getSyncUrl()
    {
        return $this->getUrl('zamp/historicalTransaction/massSync');
    }
}
