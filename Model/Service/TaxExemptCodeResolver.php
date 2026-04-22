<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Service;

use ATF\Zamp\Setup\Patch\Data\AddTaxExemptCodeCustomerAttribute;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class TaxExemptCodeResolver
{
    public const GROUP_ZAMP_TAX_EXEMPT_CODE = 'zamp_tax_exempt_code';

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param GroupFactory $groupFactory
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly GroupFactory $groupFactory
    ) {
    }

    /**
     * Effective Zamp tax exempt code: customer attribute if non-empty, else group code, else null.
     *
     * Guest or missing customer id yields null (no group-based code in v1).
     */
    public function execute(?int $customerId): ?string
    {
        if (!$customerId) {
            return null;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException) {
            return null;
        }

        $attr = $customer->getCustomAttribute(AddTaxExemptCodeCustomerAttribute::CUSTOMER_TAX_EXEMPT_CODE);
        $customerCode = $attr !== null ? trim((string)$attr->getValue()) : '';
        if ($customerCode !== '') {
            return $customerCode;
        }

        $groupId = (int)$customer->getGroupId();
        if ($groupId <= 0) {
            return null;
        }

        $group = $this->groupFactory->create()->load($groupId);
        if (!$group->getId()) {
            return null;
        }

        $groupCode = trim((string)$group->getData(self::GROUP_ZAMP_TAX_EXEMPT_CODE));
        return $groupCode !== '' ? $groupCode : null;
    }
}
