<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ValidateToken extends Field
{
    /**
     * _prepareLayout
     *
     * @return $this|ValidateToken
     */
    protected function _prepareLayout(): ValidateToken
    {
        parent::_prepareLayout();
        $this->setTemplate('ATF_Zamp::system/config/testconnection.phtml');
        return $this;
    }

    /**
     * Render
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * _getElementHtml
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('zamp/config/testConnection'),
                'field_mapping' => str_replace('"', '\\"', json_encode($this->_getFieldMapping()))
            ]
        );

        return $this->_toHtml();
    }

    /**
     * _getFieldMapping
     *
     * @return array
     */
    protected function _getFieldMapping(): array
    {
        return ['api_secret' => 'tax_zamp_configuration_api_secret',];
    }
}
