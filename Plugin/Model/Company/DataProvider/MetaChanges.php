<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Model\Company\DataProvider;

use Magento\Company\Model\Company\DataProvider;

class MetaChanges
{
    /**
     * Do not include tax_exempt_code
     *
     * @param DataProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetMeta(DataProvider $subject, array $result): array
    {
        if (isset($result['company_admin']['children']['tax_exempt_code'])) {
            unset($result['company_admin']['children']['tax_exempt_code']);
        }

        return $result;
    }
}
