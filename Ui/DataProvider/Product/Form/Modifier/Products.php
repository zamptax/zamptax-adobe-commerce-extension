<?php declare(strict_types=1);

/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use ATF\Zamp\Setup\Patch\Data\AddTaxProviderTaxCodeAttribute;

class Products extends AbstractModifier
{
    public const CUSTOM_HTML_FIELD = 'ATF_Zamp/form/field';

    /**
     * @var ArrayManager
     */
    private ArrayManager $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta): array
    {
        $taxProviderChild = AddTaxProviderTaxCodeAttribute::PRODUCT_TAX_PROVIDER_TAX_CODE;

        $containerPath = $this->arrayManager->findPath(
            static::CONTAINER_PREFIX . $taxProviderChild,
            $meta,
            null,
            'children'
        );

        if ($containerPath) {
            $meta = $this->arrayManager->merge(
                $containerPath,
                $meta,
                [
                    'children' => [
                        $taxProviderChild => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'template' => self::CUSTOM_HTML_FIELD
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            );
        }

        return $meta;
    }
}
