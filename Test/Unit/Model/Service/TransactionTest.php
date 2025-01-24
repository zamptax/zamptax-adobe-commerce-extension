<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Model\Service;

use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Service\Transaction;
use ATF\Zamp\Model\TransactionLogHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    /** @var Transaction */
    protected $transaction;

    /** @var MockHandler */
    protected $mockHandler;

    /** @var Client  */
    protected $client;

    /** @var Json|MockObject */
    protected $jsonMock;

    /** @var ScopeConfigInterface|MockObject */
    protected $scopeConfigMock;

    /** @var Logger|MockObject */
    protected $loggerMock;

    /** @var TransactionLogHandler|MockObject */
    protected $transactionLogHandlerMock;

    /** @var array */
    private $container = [];

    protected function setUp(): void
    {
        $history = Middleware::history($this->container);
        $this->mockHandler = new MockHandler($this->getTestResponses());
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);

        $this->client = new Client(['handler' => $handlerStack]);
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->jsonMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->transactionLogHandlerMock = $this->createMock(TransactionLogHandler::class);

        $this->transaction = new Transaction(
            $this->client,
            $this->jsonMock,
            $this->scopeConfigMock,
            $this->loggerMock,
            $this->transactionLogHandlerMock
        );
    }

    /**
     * @param array $responses
     * @dataProvider getListTransactionsProvider
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testListTransactions(array $responses)
    {
        $this->scopeConfigMock
            ->expects($this->exactly(2))
            ->method('getValue')
            ->with('tax/zamp_configuration/api_secret')
            ->willReturn('123TokenEncrypted');

        $this->transactionLogHandlerMock
            ->expects($this->once())
            ->method('logSuccessfulTransaction');

        $this->transactionLogHandlerMock
            ->expects($this->once())
            ->method('logFailedTransaction');

        foreach ($responses as $response) {
            $this->transaction->listTransactions();
        }

        foreach ($this->container as $transaction) {
            if ($transaction['response']) {
                $statusCode = $transaction['response']->getStatusCode();
                if ($statusCode === 200) {
                    $this->assertEquals($transaction['response']->getBody(), $responses[200]['body']);
                } elseif ($statusCode === 401) {
                    $this->assertEquals($transaction['response']->getBody(), $responses[401]['body']);
                }
            }
        }
    }

    /**
     * Get array of Response object
     *
     * @return array
     */
    public function getTestResponses(): array
    {
        $responses = $this->getListTransactionsProvider()[0];

        $responseObjects = [];

        foreach ($responses[0] as $code => $response) {
            $responseObjects[] = new Response(
                $code,
                $response['headers'],
                Utils::streamFor($response['body'])
            );

            if ($code === 401) {
                $code = 200;

                $responseObjects[] = new Response(
                    $code,
                    $responses[0][$code]['headers'],
                    Utils::streamFor($responses[0][$code]['body'])
                );
            }
        }

        return $responseObjects;
    }

    /**
     * @return array
     */
    public function getListTransactionsProvider(): array
    {
        return [[[
            200 => [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => '{"nextCursor": "x4WycXedwhQrEFuM","data": []}'
            ],
            401 => [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => '{"code":"UNAUTHORIZED","message":"Invalid auth token"}'
            ]
        ]]];
    }
}
