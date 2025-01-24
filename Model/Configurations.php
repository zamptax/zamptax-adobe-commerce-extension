<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Configurations
{
    private const MODULE_CONFIG_PATH = 'tax/zamp_configuration';
    public const REQUEST_ZAMP = 'zamp';
    public const REQUEST_ZAMP_ITEM = 'zamp_item';
    public const REQUEST_ZAMP_SHIPPING_ADDRESS = 'zamp_shipping_address';

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is Module Enabled
     *
     * @param string|null $scopeCode
     * @return bool
     */
    public function isModuleEnabled($scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::MODULE_CONFIG_PATH . '/active',
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    /**
     * Is Calculation Enabled
     *
     * @param string|null $scopeCode
     * @return bool
     */
    public function isCalculationEnabled($scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::MODULE_CONFIG_PATH . '/allow_tax_calculation',
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    /**
     * Is Taxable State
     *
     * @param AddressInterface $address
     * @return bool
     */
    public function isTaxableState(AddressInterface $address): bool
    {
        $stateId = $address->getRegion() ? $address->getRegion()->getRegionId() : null;
        if ($stateId === null) {
            return false;
        }

        $taxableStates = explode(',', $this->getTaxableState());
        return in_array((string)$stateId, $taxableStates, true);
    }

    /**
     * Is send transaction to zamp enabled
     *
     * @param string|null $scopeCode
     * @return bool
     */
    public function isSendTransactionsEnabled($scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::MODULE_CONFIG_PATH . '/send_transactions',
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    /**
     * Get Taxable State
     *
     * @param string|null $scopeCode
     * @return string|null
     */
    public function getTaxableState($scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::MODULE_CONFIG_PATH . '/taxable_states',
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        ) ?? '';
    }

    /**
     * Get default product tax code
     *
     * @param string|null $scopeCode
     * @return string|null
     */
    public function getDefaultProductTaxProviderTaxCode($scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::MODULE_CONFIG_PATH . '/default_product_tax_provider_tax_code',
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    /**
     * Get the earliest date to sync
     *
     * @return \DateTime
     */
    public function getEarliestDateToSync(): \DateTime
    {
        $date = $this->scopeConfig->getValue(self::MODULE_CONFIG_PATH . '/earliest_date');
        return new \DateTime($date);
    }

    /**
     * Is logging enabled
     *
     * @param string|null $scopeCode
     * @return bool
     */
    public function isLoggingEnabled($scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::MODULE_CONFIG_PATH . '/enable_logging',
            ScopeInterface::SCOPE_WEBSITE,
            $scopeCode
        );
    }

    /**
     * Get Log Lifetime in days
     *
     * @return string
     */
    public function getLogLifetime()
    {
        return $this->scopeConfig->getValue(
            self::MODULE_CONFIG_PATH . '/log_lifetime'
        );
    }
}
