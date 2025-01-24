<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Model\Config\Region;

use Magento\Directory\Api\CountryInformationAcquirerInterface;

class RegionInformationProvider
{
    /**
     * @var CountryInformationAcquirerInterface
     */
    private CountryInformationAcquirerInterface $countryInformationAcquirer;

    /**
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     */
    public function __construct(
        CountryInformationAcquirerInterface $countryInformationAcquirer
    ) {
        $this->countryInformationAcquirer = $countryInformationAcquirer;
    }

    /**
     * Get state options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $regions = [];
        $countries = $this->countryInformationAcquirer->getCountriesInfo();

        foreach ($countries as $country) {
            if (($country->getId() === 'US') && $availableRegions = $country->getAvailableRegions()) {
                foreach ($availableRegions as $region) {
                    $regions[] = [
                        'value' => $region->getId(),
                        'label' => $region->getName()
                    ];
                }
            }
        }

        return $regions;
    }
}
