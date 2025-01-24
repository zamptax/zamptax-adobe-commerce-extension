<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Checkout;

use ATF\Zamp\Model\Configurations;
use Magento\Checkout\Block\Cart\CartTotalsProcessor;
use Magento\Checkout\Block\Checkout\LayoutProcessor as CheckoutLayoutProcessor;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LayoutProcessorPlugin
{
    /**
     * @var Configurations
     */
    private $zampConfigurations;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Configurations $zampConfigurations
     * @param LoggerInterface $logger
     */
    public function __construct(
        Configurations $zampConfigurations,
        LoggerInterface $logger
    ) {
        $this->zampConfigurations = $zampConfigurations;
        $this->logger = $logger;
    }

    /**
     * Plugin for process method
     *
     * @param CartTotalsProcessor|CheckoutLayoutProcessor $subject
     * @param array $result
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        CartTotalsProcessor|CheckoutLayoutProcessor $subject,
        array $result,
        array $jsLayout
    ): array {
        try {
            $doZampCalc = $this->zampConfigurations->isModuleEnabled()
                && $this->zampConfigurations->isCalculationEnabled();

            if ($doZampCalc) {
                $result['components']['block-totals']['children']['tax']['config']['title'] = __('Sales Tax');
                $result['components']['checkout']['children']['sidebar']['children']['summary']
                    ['children']['totals']['children']['tax']['config']['title'] = __('Sales Tax');
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . " | " . $e->getMessage());
            $this->logger->error($e->getTraceAsString());
        }

        return $result;
    }
}
