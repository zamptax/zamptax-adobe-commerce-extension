<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Customer\Block\Adminhtml\Group;

use ATF\Zamp\Model\Service\TaxExemptCodeResolver;
use Magento\Backend\Block\Widget\Form as WidgetForm;
use Magento\Customer\Block\Adminhtml\Group\Edit\Form as CustomerGroupForm;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\GroupFactory;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

/**
 * Adds Zamp tax exempt code to the admin customer group edit form.
 */
class GroupFormAddZampField
{
    /**
     * Registry holds the current group id on edit; factory loads the row for the field default.
     * @param Registry $registry
     * @param GroupFactory $groupFactory
     */
    public function __construct(
        private readonly Registry $registry,
        private readonly GroupFactory $groupFactory
    ) {
    }

    /**
     * Inserts the text field into the base fieldset after the form is set on the group edit block.
     * @param WidgetForm $subject
     * @param WidgetForm $result
     * @param DataForm $form
     * @return WidgetForm
     * @throws LocalizedException
     */
    public function afterSetForm(WidgetForm $subject, WidgetForm $result, DataForm $form): WidgetForm
    {
        if (!$subject instanceof CustomerGroupForm) {
            return $result;
        }

        $field = TaxExemptCodeResolver::GROUP_ZAMP_TAX_EXEMPT_CODE;
        if ($form->getElement($field)) {
            return $result;
        }

        $fieldset = $form->getElement('base_fieldset');
        if (!$fieldset) {
            return $result;
        }

        $fieldset->addField(
            $field,
            'text',
            [
                'name' => $field,
                'label' => __('Zamp Tax Exempt Code'),
                'title' => __('Zamp Tax Exempt Code'),
                'note' => __(
                    'For a list of supported entity exemptions, visit Zamp\'s '
                    . '<a target="_blank" href="https://support.zamp.com/article/56-supported-entity-exemption-types">'
                    . 'documentation.</a>'
                ),
                'required' => false,
            ]
        );

        $value = '';
        $groupId = $this->registry->registry(RegistryConstants::CURRENT_GROUP_ID);
        if ($groupId !== null && $groupId !== '') {
            $group = $this->groupFactory->create()->load((int)$groupId);
            if ($group->getId()) {
                $value = (string)$group->getData($field);
            }
        }
        $form->addValues([$field => $value]);

        return $result;
    }
}
