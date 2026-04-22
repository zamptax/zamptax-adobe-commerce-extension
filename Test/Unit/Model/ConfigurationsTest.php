<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model;

use ATF\Zamp\Model\Configurations;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigurationsTest extends TestCase
{
    private const MODULE_CONFIG_PATH = 'tax/zamp_configuration';
    private $scopeConfigMock;

    private $configurations;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->configurations = new Configurations($this->scopeConfigMock);
    }

    public function testIsModuleEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(self::MODULE_CONFIG_PATH . '/active')
            ->willReturn(true);

        $this->assertTrue($this->configurations->isModuleEnabled());
    }

    public function testIsCalculationEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(self::MODULE_CONFIG_PATH . '/allow_tax_calculation')
            ->willReturn(false);

        $this->assertFalse($this->configurations->isCalculationEnabled());
    }

    public function testIsSendTransactionsEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(self::MODULE_CONFIG_PATH . '/send_transactions')
            ->willReturn(true);

        $this->assertTrue($this->configurations->isSendTransactionsEnabled());
    }

    public function testIsSendTransactionsDisabled()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(self::MODULE_CONFIG_PATH . '/send_transactions')
            ->willReturn(false);

        $this->assertFalse($this->configurations->isSendTransactionsEnabled());
    }

    public function testGetDefaultProductTaxProviderTaxCode()
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(self::MODULE_CONFIG_PATH . '/default_product_tax_provider_tax_code')
            ->willReturn('R_TPP');

        $this->assertEquals('R_TPP', $this->configurations->getDefaultProductTaxProviderTaxCode());
    }
}
