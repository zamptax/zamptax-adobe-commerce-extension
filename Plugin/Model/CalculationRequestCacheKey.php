<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Model;

use Magento\Framework\DataObject;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Sales\Quote\ItemDetails;
use Magento\Tax\Model\Sales\Total\Quote\Shipping;

class CalculationRequestCacheKey
{
    private const SHIPPING_HANDLING_CACHE_KEY = 'SHIPPING_HANDLING';
    private const COUNTRY_KEYS = ['country_id', 'countryId', 'country'];
    private const REGION_KEYS = ['region_id', 'regionId', 'region'];
    private const POSTCODE_KEYS = ['postcode', 'post_code', 'postal_code'];

    /**
     * Before Get Rate
     *
     * @param Calculation $subject
     * @param DataObject $request
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetRate(
        Calculation $subject,
        DataObject  $request
    ): array {
        if (!$request->getZamp()) {
            return [$request];
        }

        $locationKey = $this->buildLocationKey($request);

        /** @var ItemDetails $item */
        $item = $request->getZampItem();
        if ($item->getType() === Shipping::ITEM_CODE_SHIPPING) {
            $request->setProductClassId(
                $this->buildProductClassCacheKey(self::SHIPPING_HANDLING_CACHE_KEY, $locationKey)
            );
            return [$request];
        }

        $extension = $item->getExtensionAttributes();
        if ($extension && $extension->getProductTaxCode()) {
            $taxCode = $extension->getProductTaxCode();
            $request->setProductClassId($this->buildProductClassCacheKey((string)$taxCode, $locationKey));
        }

        return [$request];
    }

    /**
     * Adds location context to avoid cross-address tax cache collisions.
     *
     * @param string $baseKey
     * @param string $locationKey
     * @return string
     */
    private function buildProductClassCacheKey(string $baseKey, string $locationKey): string
    {
        if ($locationKey === '') {
            return $baseKey;
        }

        return $baseKey . '|' . $locationKey;
    }

    /**
     * Builds a location key for the tax calculation request.
     *
     * @param DataObject $request
     * @return string
     */
    private function buildLocationKey(DataObject $request): string
    {
        $country = $this->extractRequestValue($request, self::COUNTRY_KEYS);
        $region = $this->extractRequestValue($request, self::REGION_KEYS);
        $postcode = $this->extractRequestValue($request, self::POSTCODE_KEYS);

        return implode('|', [$country, $region, $postcode]);
    }

    /**
     * Tries common getter/data keys used by Magento tax requests.
     *
     * @param DataObject $request
     * @param string[] $keys
     * @return string
     */
    private function extractRequestValue(DataObject $request, array $keys): string
    {
        foreach ($keys as $key) {
            $value = (string)$request->getData($key);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
