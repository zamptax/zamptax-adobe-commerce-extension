<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\ViewModel;

use ATF\Zamp\Services\Quote as QuoteService;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Framework\View\DesignInterface;
use Magento\Theme\Model\Theme;

class TaxViewModel implements ArgumentInterface
{
    /**
     * @var DesignInterface
     */
    private DesignInterface $design;

    /**
     * @var Theme
     */
    private Theme $theme;

    /**
     * @param DesignInterface $design
     * @param Theme $theme
     */
    public function __construct(
        DesignInterface $design,
        Theme $theme
    ) {
        $this->design = $design;
        $this->theme = $theme;
    }

    /**
     * Check if tax label should be changed
     *
     * @param Order|Quote $order
     * @param string $defaultTitle
     * @return string
     */
    public function getTaxLabel(Order|Quote $order, string $defaultTitle): string
    {
        $taxLabel = $defaultTitle;

        if ($order->getData(QuoteService::IS_ZAMP_CALCULATED)) {
            $taxLabel = QuoteService::ZAMP_TAX_LABEL . ' ' . $defaultTitle;
        }

        return $taxLabel;
    }

    /**
     * Get the current theme name
     *
     * @return string
     */
    public function getCurrentThemeCode(): string
    {
        $themeId = $this->design->getDesignTheme()->getId();
        $theme = $this->theme->load($themeId);

        return $theme->getCode();
    }

    /**
     * Check if theme string parameter is Hyva
     *
     * @param string $code
     * @return bool
     */
    public function checkIfThemeIsHyvaByThemeCode(string $code): bool
    {
        if (stripos($code, 'Hyva') !== false
            || stripos($code, 'Hyvä') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Check if current theme is Hyva
     *
     * @return bool
     */
    public function checkIfCurrentThemeIsHyvaByThemeCode(): bool
    {
        $theme = $this->getCurrentThemeCode();
        return $this->checkIfThemeIsHyvaByThemeCode($theme);
    }
}
