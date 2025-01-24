<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Block\Adminhtml\TransactionLog;

class Container extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Add button
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $backButton = [
            'id' => 'back',
            'label' => __('Back'),
            'class' => 'back',
            'onclick' => 'setLocation(\'' . $this->getBackUrl() . '\')',
        ];
        $this->buttonList->add('back', $backButton);

        return parent::_prepareLayout();
    }

    /**
     * Get URL for back button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }
}
