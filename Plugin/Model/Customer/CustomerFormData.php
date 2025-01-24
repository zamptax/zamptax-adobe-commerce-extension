<?php declare(strict_types=1);

/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Model\Customer;

use Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses;
use ATF\Zamp\Ui\DataProvider\Product\Form\Modifier\Products;
use ATF\Zamp\Setup\Patch\Data\AddTaxExemptCodeCustomerAttribute;

class CustomerFormData
{
    /**
     * Update template using plugin to be able to cater the data uninstall
     *
     * @param DataProviderWithDefaultAddresses $subject
     * @param array $result
     * @return array
     */
    public function afterGetMeta(
        DataProviderWithDefaultAddresses $subject,
        array $result
    ): array {
        $code = AddTaxExemptCodeCustomerAttribute::CUSTOMER_TAX_EXEMPT_CODE;

        if (isset($result['customer']['children'][$code])) {
            $result['customer']['children'][$code]['arguments']
            ['data']['config']['template'] = Products::CUSTOM_HTML_FIELD;
        }

        return $result;
    }
}
