<?php declare(strict_types=1);

/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Plugin\Model\Customer;

use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses;
use ATF\Zamp\Plugin\Model\Customer\CustomerFormData;
use ATF\Zamp\Ui\DataProvider\Product\Form\Modifier\Products;
use ATF\Zamp\Setup\Patch\Data\AddTaxExemptCodeCustomerAttribute;

class CustomerFormDataTest extends TestCase
{
    /**
     * @var CustomerFormData
     */
    private $customerFormData;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerFormData = new CustomerFormData();
    }

    /**
     * @return void
     */
    public function testAfterGetMeta(): void
    {
        $subjectMock = $this->createMock(DataProviderWithDefaultAddresses::class);
        $result = [
            'customer' => [
                'children' => [
                    AddTaxExemptCodeCustomerAttribute::CUSTOMER_TAX_EXEMPT_CODE => [
                        'arguments' => [
                            'data' => [
                                'config' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $expectedResult = $result;
        $expectedResult['customer']['children'][AddTaxExemptCodeCustomerAttribute::CUSTOMER_TAX_EXEMPT_CODE]
        ['arguments']['data']['config']['template'] = Products::CUSTOM_HTML_FIELD;

        $actualResult = $this->customerFormData->afterGetMeta($subjectMock, $result);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return void
     */
    public function testAfterGetMetaWithoutTaxExemptCode(): void
    {
        $subjectMock = $this->createMock(DataProviderWithDefaultAddresses::class);

        $result = [
            'customer' => [
                'children' => [
                    'other_attribute' => [
                        'arguments' => [
                            'data' => [
                                'config' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $actualResult = $this->customerFormData->afterGetMeta($subjectMock, $result);
        $this->assertEquals($result, $actualResult);
    }
}
