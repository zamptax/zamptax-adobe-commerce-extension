<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Model\Config\Backend;

use PHPUnit\Framework\TestCase;
use ATF\Zamp\Model\Config\Backend\Encrypted;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;

class EncryptedTest extends TestCase
{
    /**
     * @var Encrypted
     */
    private $encrypted;

    /**
     * @var Context
     */
    private $contextMock;

    /**
     * @var Registry
     */
    private $registryMock;

    /**
     * @var ScopeConfigInterface
     */
    private $configMock;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeListMock;

    /**
     * @var EncryptorInterface
     */
    private $encryptorMock;

    /**
     * @var AbstractResource
     */
    private $resourceMock;

    /**
     * @var AbstractDb
     */
    private $resourceCollectionMock;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->configMock = $this->createMock(ScopeConfigInterface::class);
        $this->cacheTypeListMock = $this->createMock(TypeListInterface::class);
        $this->encryptorMock = $this->createMock(EncryptorInterface::class);
        $this->resourceMock = $this->createMock(AbstractResource::class);
        $this->resourceCollectionMock = $this->createMock(AbstractDb::class);

        $this->encrypted = new Encrypted(
            $this->contextMock,
            $this->registryMock,
            $this->configMock,
            $this->cacheTypeListMock,
            $this->encryptorMock,
            $this->resourceMock,
            $this->resourceCollectionMock
        );
    }

    public function testBeforeSaveThrowsExceptionForInvalidTokenLength()
    {
        $this->encrypted->setValue('invalid_token_length');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('API Token must contain exactly 64 characters.');

        // Call beforeSave and expect exception
        $this->encrypted->beforeSave();
    }

    public function testBeforeSaveAllowsSaveForValidToken()
    {
        $validToken = str_repeat('a', 64); // Create a valid token with exactly 64 characters
        $this->encrypted->setValue($validToken);

        $encryptedValue = 'encrypted_value';

        // Mock the encrypt method to return a predefined encrypted value
        $this->encryptorMock->method('encrypt')->with($validToken)->willReturn($encryptedValue);

        // Call beforeSave
        $this->encrypted->beforeSave();

        // Assert that the value was encrypted
        $this->assertEquals($encryptedValue, $this->encrypted->getValue());
    }
}
