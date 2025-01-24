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

namespace ATF\Zamp\Test\Unit\Plugin\Model;

use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Plugin\Model\CheckoutConfigProviderPlugin;
use ATF\Zamp\ViewModel\TaxViewModel;
use Magento\Checkout\Model\DefaultConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutConfigProviderPluginTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $configurationsMock;

    /**
     * @var MockObject
     */
    private $taxViewModelMock;

    /**
     * @var CheckoutConfigProviderPlugin
     */
    private $checkoutConfigProviderPlugin;

    /**
     * Set up mocks and initialize the plugin class.
     */
    protected function setUp(): void
    {
        $this->configurationsMock = $this->createMock(Configurations::class);
        $this->taxViewModelMock = $this->createMock(TaxViewModel::class);

        // Initialize the plugin with mocked dependencies
        $this->checkoutConfigProviderPlugin = new CheckoutConfigProviderPlugin(
            $this->configurationsMock,
            $this->taxViewModelMock
        );
    }

    /**
     * Test the afterGetConfig method when Sales Tax should be applied.
     */
    public function testAfterGetConfigWithZampTaxEnabledAndHyvaTheme(): void
    {
        $result = [
            'totalsData' => [
                'total_segments' => [
                    [
                        'code' => 'tax',
                        'title' => 'Original Tax Title'
                    ]
                ]
            ]
        ];

        $this->configurationsMock->method('isModuleEnabled')->willReturn(true);
        $this->configurationsMock->method('isCalculationEnabled')->willReturn(true);
        $this->taxViewModelMock->method('checkIfCurrentThemeIsHyvaByThemeCode')->willReturn(true);

        $subjectMock = $this->createMock(DefaultConfigProvider::class);
        $modifiedResult = $this->checkoutConfigProviderPlugin->afterGetConfig($subjectMock, $result);
        $this->assertEquals('Sales Tax', $modifiedResult['totalsData']['total_segments'][0]['title']);
    }

    /**
     * Test the afterGetConfig method when Zamp Tax is not applied.
     */
    public function testAfterGetConfigWithoutZampTax(): void
    {
        $result = [
            'totalsData' => [
                'total_segments' => [
                    [
                        'code' => 'tax',
                        'title' => 'Original Tax Title'
                    ]
                ]
            ]
        ];

        $this->configurationsMock->method('isModuleEnabled')->willReturn(false);
        $this->configurationsMock->method('isCalculationEnabled')->willReturn(false);
        $this->taxViewModelMock->method('checkIfCurrentThemeIsHyvaByThemeCode')->willReturn(false);
        $subjectMock = $this->createMock(DefaultConfigProvider::class);
        $modifiedResult = $this->checkoutConfigProviderPlugin->afterGetConfig($subjectMock, $result);
        $this->assertEquals('Original Tax Title', $modifiedResult['totalsData']['total_segments'][0]['title']);
    }
}
