<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model\Config\Region;

use PHPUnit\Framework\TestCase;
use ATF\Zamp\Model\Config\Region\RegionInformationProvider;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Directory\Api\Data\RegionInformationInterface;

class RegionInformationProviderTest extends TestCase
{
    /**
     * @var RegionInformationProvider
     */
    private $regionInformationProvider;

    /**
     * @var CountryInformationAcquirerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $countryInformationAcquirerMock;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        // Mock the CountryInformationAcquirerInterface
        $this->countryInformationAcquirerMock = $this->createMock(CountryInformationAcquirerInterface::class);

        // Initialize the class with the mock dependency
        $this->regionInformationProvider = new RegionInformationProvider(
            $this->countryInformationAcquirerMock
        );
    }

    public function testToOptionArrayReturnsCorrectRegions()
    {
        // Mock region data
        $regionMock = $this->createMock(RegionInformationInterface::class);
        $regionMock->method('getId')->willReturn(12);
        $regionMock->method('getName')->willReturn('California');

        // Mock country data
        $countryMock = $this->createMock(CountryInformationInterface::class);
        $countryMock->method('getId')->willReturn('US');
        $countryMock->method('getAvailableRegions')->willReturn([$regionMock]);

        // Mock the getCountriesInfo() method
        $this->countryInformationAcquirerMock->method('getCountriesInfo')->willReturn([$countryMock]);

        // Call the method under test
        $result = $this->regionInformationProvider->toOptionArray();

        // Assert the result
        $this->assertCount(1, $result);
        $this->assertEquals(12, $result[0]['value']);
        $this->assertEquals('California', $result[0]['label']);
    }

    public function testToOptionArrayReturnsEmptyArrayForNoRegions()
    {
        // Mock country data without regions
        $countryMock = $this->createMock(CountryInformationInterface::class);
        $countryMock->method('getId')->willReturn('US');
        $countryMock->method('getAvailableRegions')->willReturn([]);

        // Mock the getCountriesInfo() method
        $this->countryInformationAcquirerMock->method('getCountriesInfo')->willReturn([$countryMock]);

        // Call the method under test
        $result = $this->regionInformationProvider->toOptionArray();

        // Assert the result is an empty array
        $this->assertEmpty($result);
    }

    public function testToOptionArrayIgnoresNonUSCountries()
    {
        // Mock a non-US country
        $countryMock = $this->createMock(CountryInformationInterface::class);
        $countryMock->method('getId')->willReturn('CA'); // Canada
        $countryMock->method('getAvailableRegions')->willReturn([]);

        // Mock the getCountriesInfo() method
        $this->countryInformationAcquirerMock->method('getCountriesInfo')->willReturn([$countryMock]);

        // Call the method under test
        $result = $this->regionInformationProvider->toOptionArray();

        // Assert the result is an empty array since no US regions exist
        $this->assertEmpty($result);
    }
}
