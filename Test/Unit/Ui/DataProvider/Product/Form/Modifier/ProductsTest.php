<?php declare(strict_types=1);

/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use ATF\Zamp\Ui\DataProvider\Product\Form\Modifier\Products;
use ATF\Zamp\Setup\Patch\Data\AddTaxProviderTaxCodeAttribute;
use Magento\Framework\Stdlib\ArrayManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProductsTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $arrayManagerMock;

    /**
     * @var Products
     */
    private $productsModifier;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->productsModifier = new Products(
            $this->arrayManagerMock
        );
    }

    /**
     * @return void
     */
    public function testModifyMetaAddsTaxProviderChildConfig(): void
    {
        $taxProviderChild = AddTaxProviderTaxCodeAttribute::PRODUCT_TAX_PROVIDER_TAX_CODE;
        $meta = $this->buildArrayData('Magento_Catalog/form/field', $taxProviderChild);
        $expected = $this->buildArrayData('ATF_Zamp/form/field', $taxProviderChild);

        $containerPath = 'product-details/children/container_' . $taxProviderChild;
        $this->arrayManagerMock->expects($this->once())
            ->method('findPath')
            ->willReturn($containerPath);
        $this->arrayManagerMock->expects($this->once())
            ->method('merge')
            ->willReturn($expected);

        $result = $this->productsModifier->modifyMeta($meta);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testModifyMetaAddsTaxProviderChildConfigNoPath(): void
    {
        $taxProviderChild = AddTaxProviderTaxCodeAttribute::PRODUCT_TAX_PROVIDER_TAX_CODE;
        $meta = $this->buildArrayData('Magento_Catalog/form/field', $taxProviderChild);

        $this->arrayManagerMock->expects($this->once())
            ->method('findPath')
            ->willReturn(null);

        $result = $this->productsModifier->modifyMeta($meta);

        $this->assertEquals($meta, $result);
    }

    /**
     * Build array data for the meta data
     *
     * @param string $template
     * @param string $taxProviderChild
     * @return array
     */
    public function buildArrayData(string $template, string $taxProviderChild): array
    {
        return [
            'product-details' => [
                'children' => [
                    'container_' . $taxProviderChild => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => 'Some Field',
                                ]
                            ]
                        ],
                        'children' => [
                            $taxProviderChild => [
                                'arguments' => [
                                    'template' => $template,
                                    'label' => 'Some Field Inner'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
