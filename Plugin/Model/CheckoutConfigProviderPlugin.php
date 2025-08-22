<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 *
 * @category  ATF
 * @package   ATF_ZampHyvaCompatibility
 * @author    Above The Fray
 * @copyright 2024 Above The Fray
 * @license   see ATF_COPYING.txt
 * @link      https://abovethefray.io/
 */

namespace ATF\Zamp\Plugin\Model;

use ATF\Zamp\Model\Configurations;
use ATF\Zamp\ViewModel\TaxViewModel;
use Magento\Checkout\Model\DefaultConfigProvider;

class CheckoutConfigProviderPlugin
{
    /**
     * @var Configurations
     */
    private Configurations $configurations;

    /**
     * @var TaxViewModel
     */
    private TaxViewModel $taxViewModel;

    /**
     * @param Configurations $configurations
     * @param TaxViewModel $taxViewModel
     */
    public function __construct(
        Configurations $configurations,
        TaxViewModel $taxViewModel
    ) {
        $this->configurations = $configurations;
        $this->taxViewModel = $taxViewModel;
    }

    /**
     * After plugin for getConfig method
     *
     * @param DefaultConfigProvider $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetConfig(DefaultConfigProvider $subject, array $result): array
    {
        $isHyvaTheme = $this->taxViewModel->checkIfCurrentThemeIsHyvaByThemeCode();
        $isZampCalculatedIsEnabled = $this->configurations->isModuleEnabled()
            && $this->configurations->isCalculationEnabled();

        if (isset($result['totalsData']['total_segments']) && $isZampCalculatedIsEnabled && $isHyvaTheme) {
            foreach ($result['totalsData']['total_segments'] as &$segment) {
                if (isset($segment['code']) && $segment['code'] === 'tax') {
                    $segment['title'] = 'Sales Tax';
                }
            }
        }

        return $result;
    }
}
