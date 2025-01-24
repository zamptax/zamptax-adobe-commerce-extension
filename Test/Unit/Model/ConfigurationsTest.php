<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model;

use ATF\Zamp\Model\Configurations;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Directory\Model\Region;
use PHPUnit\Framework\TestCase;

class ConfigurationsTest extends TestCase
{
    private const MODULE_CONFIG_PATH = 'tax/zamp_configuration';
    public const REQUEST_ZAMP = 'zamp';
    public const REQUEST_ZAMP_ITEM = 'zamp_item';
    public const REQUEST_ZAMP_SHIPPING_ADDRESS = 'zamp_shipping_address';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    /**
     * @var AddressInterface
     */
    private $addressMock;

    /**
     * @var Region
     */
    private $regionMock;

    /**
     * @var Configurations
     */
    private $configurations;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->addressMock = $this->createMock(AddressInterface::class);

        $this->regionMock = $this->getMockBuilder(Region::class)
            ->addMethods(['getRegionId'])
            ->disableOriginalConstructor()
            ->getMock();

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

    public function testGetTaxableState()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(self::MODULE_CONFIG_PATH . '/taxable_states')
            ->willReturn('CA,NY,TX');

        $this->assertEquals('CA,NY,TX', $this->configurations->getTaxableState());
    }

    public function testIsTaxableStateWithValidState()
    {
        $this->addressMock->method('getRegion')
            ->willReturn($this->regionMock);

        $this->regionMock->method('getRegionId')
            ->willReturn('CA'); // Mocked state

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(self::MODULE_CONFIG_PATH . '/taxable_states')
            ->willReturn('CA,NY,TX');

        $this->assertTrue($this->configurations->isTaxableState($this->addressMock));
    }

    public function testIsTaxableStateWithInvalidState()
    {
        $this->addressMock->method('getRegion')
            ->willReturn($this->regionMock);

        $this->regionMock->method('getRegionId')
            ->willReturn('FL'); // Non-taxable state

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(self::MODULE_CONFIG_PATH . '/taxable_states')
            ->willReturn('CA,NY,TX');

        $this->assertFalse($this->configurations->isTaxableState($this->addressMock));
    }

    public function testIsTaxableStateWithNullState()
    {
        $this->addressMock
            ->method('getRegion')
            ->willReturn(null);

        $this->assertFalse($this->configurations->isTaxableState($this->addressMock));
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
