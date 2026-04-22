<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Customer\Api\GroupRepository;

use ATF\Zamp\Model\Service\TaxExemptCodeResolver;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\GroupRegistry;
use Magento\Customer\Model\ResourceModel\Group as GroupResource;
use Magento\Framework\App\Area;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Persists zamp_tax_exempt_code after GroupRepository::save() for the admin customer group form.
 * Does not apply to NOT LOGGED IN (customer_group_id 0). Uses resource model save, not direct SQL.
 */
class AfterSavePersistZampTaxExempt
{
    /**
     * @param GroupFactory $groupFactory
     * @param GroupResource $groupResource
     * @param GroupRegistry $groupRegistry
     * @param RequestInterface $request
     * @param State $appState
     */
    public function __construct(
        private readonly GroupFactory $groupFactory,
        private readonly GroupResource $groupResource,
        private readonly GroupRegistry $groupRegistry,
        private readonly RequestInterface $request,
        private readonly State $appState
    ) {
    }

    /**
     * After API save, persist posted zamp_tax_exempt_code on the group model (skips id 0).
     *
     * @param GroupRepositoryInterface $subject
     * @param GroupInterface $result
     * @param GroupInterface $group
     * @return GroupInterface
     * @throws AlreadyExistsException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        GroupRepositoryInterface $subject,
        GroupInterface $result,
        GroupInterface $group
    ): GroupInterface {
        $groupId = $result->getId();
        if ($groupId === null || $groupId === '') {
            return $result;
        }

        $groupId = (int)$groupId;
        if ($groupId === Group::NOT_LOGGED_IN_ID) {
            return $result;
        }

        if (!$this->isApplicableAdminGroupZampPost()) {
            return $result;
        }

        $groupModel = $this->groupFactory->create();
        $this->groupResource->load($groupModel, $groupId);
        if (!$groupModel->getId()) {
            return $result;
        }

        $field = TaxExemptCodeResolver::GROUP_ZAMP_TAX_EXEMPT_CODE;
        $value = trim((string)$this->request->getParam($field));
        $groupModel->setData($field, $value === '' ? null : $value);

        $this->groupResource->save($groupModel);
        $this->groupRegistry->remove($groupId);

        return $result;
    }

    /**
     * Whether the current request is an admin customer-group save that includes the Zamp field.
     * @return bool
     */
    private function isApplicableAdminGroupZampPost(): bool
    {
        try {
            if ($this->appState->getAreaCode() !== Area::AREA_ADMINHTML) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        if (!$this->request instanceof HttpRequest || !$this->request->isPost()) {
            return false;
        }

        if (!(int)$this->request->getParam('tax_class')) {
            return false;
        }

        $field = TaxExemptCodeResolver::GROUP_ZAMP_TAX_EXEMPT_CODE;
        return array_key_exists($field, $this->request->getParams());
    }
}
