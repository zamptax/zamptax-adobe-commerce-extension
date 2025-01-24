<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Transaction;

use Magento\Directory\Model\Region;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class ShipToAddress extends DataObject
{
    public const LINE1 = 'line1';
    public const LINE2 = 'line2';
    public const STATE = 'state';
    public const CITY = 'city';
    public const ZIP = 'zip';
    public const COUNTRY = 'country';

    /**
     * @var Collection
     */
    protected Collection $regionCollection;

    /**
     * @param RegionCollectionFactory $regionCollectionFactory
     * @param array $data
     */
    public function __construct(
        RegionCollectionFactory $regionCollectionFactory,
        array                   $data = []
    ) {
        parent::__construct($data);
        $this->regionCollection = $regionCollectionFactory->create();
    }

    /**
     * Get Line 1
     *
     * @return string
     */
    public function getLine1(): string
    {
        return $this->getData(self::LINE1);
    }

    /**
     * Set Line 1
     *
     * @param string|null $line1
     * @return ShipToAddress
     */
    public function setLine1(?string $line1): ShipToAddress
    {
        return $this->setData(self::LINE1, $line1);
    }

    /**
     * Get Line 2
     *
     * @return string
     */
    public function getLine2(): string
    {
        return $this->getData(self::LINE2);
    }

    /**
     * Set Line 2
     *
     * @param string|null $line2
     * @return ShipToAddress
     */
    public function setLine2(?string $line2): ShipToAddress
    {
        return $this->setData(self::LINE2, $line2);
    }

    /**
     * Get State
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->getData(self::STATE);
    }

    /**
     * Set State
     *
     * @param string $state
     * @return ShipToAddress
     */
    public function setState(string $state): ShipToAddress
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * Get City
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->getData(self::CITY);
    }

    /**
     * Set City
     *
     * @param string|null $city
     * @return ShipToAddress
     */
    public function setCity(?string $city): ShipToAddress
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * Get Zip
     *
     * @return string
     */
    public function getZip(): string
    {
        return $this->getData(self::ZIP);
    }

    /**
     * Set Zip
     *
     * @param string|null $zip
     * @return ShipToAddress
     */
    public function setZip(?string $zip): ShipToAddress
    {
        return $this->setData(self::ZIP, $zip);
    }

    /**
     * Get Country
     *
     * @return string
     */
    public function getCountry(): string
    {
        return $this->getData(self::COUNTRY);
    }

    /**
     * Set Country
     *
     * @param string $country
     * @return ShipToAddress
     */
    public function setCountry(string $country): ShipToAddress
    {
        return $this->setData(self::COUNTRY, $country);
    }

    /**
     * Get Region Code By Id
     *
     * @param mixed $regionId
     * @return string|null
     * @throws LocalizedException
     */
    public function getRegionCodeById($regionId): ?string
    {
        if (!$regionId) {
            return null;
        }

        // Countries with free-form regions can't be loaded from the database, so just return it as the code
        if (!is_numeric($regionId)) {
            return $regionId;
        }

        /** @var Region $region */
        $region = $this->regionCollection->getItemById($regionId);

        if (!($region instanceof Region)) {
            throw new LocalizedException(__(
                'Region "%1" was not found.',
                [
                    $regionId,
                ]
            ));
        }

        return $region->getCode();
    }
}
