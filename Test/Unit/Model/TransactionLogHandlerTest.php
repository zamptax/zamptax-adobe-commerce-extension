<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model;

use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\TransactionLog;
use ATF\Zamp\Model\TransactionLogFactory;
use ATF\Zamp\Model\TransactionLogHandler;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionLogHandlerTest extends TestCase
{
    /**
     * @var TransactionLogHandler
     */
    protected $handler;
    
    /**
     * @var Configurations|MockObject
     */
    protected $config;

    /**
     * @var TransactionLogFactory|MockObject
     */
    protected $transactionLogFactory;

    /**
     * @var TransactionLog|MockObject
     */
    protected $transactionLog;

    /**
     * @var JsonSerializer|MockObject
     */
    protected $json;

    /**
     * @var GuzzleException|MockObject
     */
    protected $guzzleException;

    /**
     * @var Response|MockObject
     */
    protected $response;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Configurations::class);

        $this->transactionLogFactory = $this->createMock(TransactionLogFactory::class);

        $this->transactionLog = $this->getMockBuilder(TransactionLog::class)
            ->onlyMethods(['save'])
            ->addMethods(['setRequest', 'setResponse', 'setEndpoint', 'setHttpMethod', 'setStatus'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->createMock(JsonSerializer::class);

        $this->guzzleException = $this->getMockBuilder(GuzzleException::class)
            ->addMethods(['hasResponse','getResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->createMock(Response::class);

        $this->handler = new TransactionLogHandler($this->transactionLogFactory, $this->config, $this->json);
    }

    /**
     * Test logSuccessfulTransaction method
     */
    public function testLogSuccessfulTransaction(): void
    {
        $data = [
            'request' => '{"id":1}',
            'response' => '{"id":1}',
            'method' => 'POST',
            'endpoint' => 'transaction',
        ];

        extract($data);

        $this->assertLogSaving(
            $request,
            $response,
            $method,
            $endpoint,
            1
        );

        $this->handler->logSuccessfulTransaction(
            $method,
            $endpoint,
            $request,
            $response
        );
    }

    /**
     * Test logFailedTransaction method
     */
    public function testLogFailedTransaction(): void
    {
        $data = [
            'request' => '{"id":1}',
            'response' => '{"code":400}',
            'method' => 'POST',
            'endpoint' => 'transaction'
        ];
        extract($data);

        $this->guzzleException
            ->expects($this->once())
            ->method('hasResponse')
            ->willReturn(true);
        $this->guzzleException
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);
        $this->response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(Utils::streamFor($response));

        $this->assertLogSaving(
            $request,
            $response,
            $method,
            $endpoint,
            2
        );

        $this->handler->logFailedTransaction(
            $method,
            $endpoint,
            $request,
            $this->guzzleException
        );
    }

    public function assertLogSaving($request, $response, $method, $endpoint, $status)
    {
        $this->config
            ->expects($this->once())
            ->method('isLoggingEnabled')
            ->willReturn(true);

        $this->transactionLogFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->transactionLog);

        $this->transactionLog
            ->expects($this->once())
            ->method('setRequest')
            ->with($request)
            ->willReturnSelf();

        $this->transactionLog
            ->expects($this->once())
            ->method('setResponse')
            ->with($response)
            ->willReturnSelf();

        $this->transactionLog
            ->expects($this->once())
            ->method('setHttpMethod')
            ->with($method)
            ->willReturnSelf();

        $this->transactionLog
            ->expects($this->once())
            ->method('setEndpoint')
            ->with($endpoint)
            ->willReturnSelf();

        $this->transactionLog
            ->expects($this->once())
            ->method('setStatus')
            ->with($status)
            ->willReturnSelf();

        $this->transactionLog
            ->expects($this->once())
            ->method('save')
            ->willReturnSelf();
    }
}
