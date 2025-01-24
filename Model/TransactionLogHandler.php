<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class TransactionLogHandler
{
    /**
     * @var TransactionLogFactory
     */
    protected $transactionLogFactory;

    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @param TransactionLogFactory $transactionLogFactory
     * @param Configurations $configurations
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        TransactionLogFactory $transactionLogFactory,
        Configurations $configurations,
        JsonSerializer $jsonSerializer
    ) {
        $this->transactionLogFactory = $transactionLogFactory;
        $this->config = $configurations;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Log transaction
     *
     * @param string $method
     * @param string $endpoint
     * @param string $request
     * @param string $response
     * @return void
     */
    public function logSuccessfulTransaction($method, $endpoint, $request, $response)
    {
        $this->saveLog(
            $request,
            (string)$response,
            $method,
            $endpoint,
            TransactionLog::RESPONSE_STATUS_SUCCESS
        );
    }

    /**
     * Log failed transaction
     *
     * @param string $method
     * @param string $endpoint
     * @param string $request
     * @param GuzzleException $exception
     * @return void
     */
    public function logFailedTransaction($method, $endpoint, $request, $exception)
    {
        $response = '';
        if ($exception->hasResponse()) {
            $response = (string)$exception->getResponse()->getBody();
        }

        $this->saveLog(
            $request,
            $response,
            $method,
            $endpoint,
            TransactionLog::RESPONSE_STATUS_ERROR
        );
    }

    /**
     * Add new entry
     *
     * @param string $request
     * @param string $response
     * @param string $method
     * @param string $endpoint
     * @param string $status
     * @return void
     */
    protected function saveLog($request, $response, $method, $endpoint, $status)
    {
        if (!$this->config->isLoggingEnabled()) {
            return;
        }

        $transactionLog = $this->transactionLogFactory->create();
        $transactionLog
            ->setRequest($request)
            ->setResponse($response)
            ->setHttpMethod($method)
            ->setEndpoint($endpoint)
            ->setStatus($status)
            ->save();
    }
}
