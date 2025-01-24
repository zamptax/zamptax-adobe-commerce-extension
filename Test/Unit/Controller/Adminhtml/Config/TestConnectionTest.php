<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Controller\Adminhtml\Config;

use ATF\Zamp\Controller\Adminhtml\Config\TestConnection;
use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Service\Transaction;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filter\StripTags;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TestConnectionTest extends TestCase
{
    /**
     * @var TestConnection
     */
    private $testConnection;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var StripTags|MockObject
     */
    private $tagFilter;

    /**
     * @var Transaction|MockObject
     */
    private $transaction;

    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var Json|MockObject
     */
    private $jsonResult;

    protected function setUp(): void
    {
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->tagFilter = $this->createMock(StripTags::class);
        $this->transaction = $this->createMock(Transaction::class);
        $this->logger = $this->createMock(Logger::class);
        $this->jsonResult = $this->createMock(Json::class);

        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResult);

        $this->testConnection = new TestConnection(
            $this->resultJsonFactory,
            $this->request,
            $this->tagFilter,
            $this->transaction,
            $this->logger
        );
    }

    /**
     * Test successful connection scenario.
     */
    public function testExecuteSuccessfulConnection(): void
    {
        $this->request->method('getParams')
            ->willReturn(['api_secret' => 'valid_token']);

        $this->tagFilter->expects($this->any())
            ->method('filter')
            ->willReturnArgument(0);

        $this->transaction->expects($this->once())
            ->method('listTransactions')
            ->willReturn(['data' => 'transaction_data']);

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with([
                'success' => true,
                'errorMessage' => '',
            ]);

        $this->testConnection->execute();
    }

    /**
     * Test missing API token scenario.
     */
    public function testExecuteMissingApiToken(): void
    {
        $this->request->method('getParams')
            ->willReturn([]);

        $this->tagFilter->expects($this->any())
            ->method('filter')
            ->willReturnArgument(0);

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with([
                'success' => false,
                'errorMessage' => 'API Token is required.',
            ]);

        $this->testConnection->execute();
    }

    /**
     * Test invalid API token scenario.
     */
    public function testExecuteInvalidApiToken()
    {
        $this->request->method('getParams')
            ->willReturn(['api_secret' => 'invalid_token']);

        $this->tagFilter->expects($this->any())
            ->method('filter')
            ->willReturnArgument(0);

        $this->transaction->expects($this->once())
            ->method('listTransactions')
            ->willThrowException(new \Exception('Invalid token'));

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with([
                'success' => false,
                'errorMessage' => 'An error occurred while testing the connection.',
            ]);

        $this->testConnection->execute();
    }

    /**
     * Test general exception handling scenario.
     */
    public function testExecuteGeneralExceptionHandling(): void
    {
        $this->request->method('getParams')
            ->willReturn(['api_secret' => 'valid_token']);

        $this->tagFilter->expects($this->any())
            ->method('filter')
            ->willReturnArgument(0);

        $this->transaction->expects($this->once())
            ->method('listTransactions')
            ->willThrowException(new \Exception('General error'));

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with([
                'success' => false,
                'errorMessage' => 'An error occurred while testing the connection.',
            ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in TestConnection: General error'));

        $this->testConnection->execute();
    }
}
