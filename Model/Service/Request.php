<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Service;

use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\TransactionLogHandler;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

abstract class Request
{
    public const LOG_DEBUG = false;

    public const API_URL = 'https://api.zamp.com';

    private const CONFIG_PATH_API_TOKEN = 'tax/zamp_configuration/api_secret';

    private const MAX_RETRIES = 5;

    /**
     * @var GuzzleClient
     */
    private GuzzleClient $guzzleClient;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var TransactionLogHandler
     */
    protected $transactionLogHandler;

    /**
     * @var array|null
     */
    private $response;

    /**
     * @param GuzzleClient $guzzleClient
     * @param Json $json
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param TransactionLogHandler $transactionLogHandler
     */
    public function __construct(
        GuzzleClient         $guzzleClient,
        Json                 $json,
        ScopeConfigInterface $scopeConfig,
        Logger               $logger,
        TransactionLogHandler $transactionLogHandler
    ) {
        $this->guzzleClient = $guzzleClient;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->transactionLogHandler = $transactionLogHandler;
    }

    /**
     * Get endpoint url
     *
     * @return string
     */
    abstract public function getEndpoint(): string;

    /**
     * Send request to API
     *
     * @param string $method
     * @param string $endpointSuffix the string after endpoint like transaction id
     * @param array $data
     * @param int $retries
     * @return array
     */
    protected function sendRequest(
        string $method,
        string $endpointSuffix = '',
        array  $data = [],
        int    $retries = 1
    ): mixed {
        $this->response = null;
        $unqId = uniqid('DEBUG', true);
        $endpoint = $this->getEndpoint();
        $endpoint .= $endpointSuffix ? '/' . $endpointSuffix : '';

        $options['headers'] = [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/json'
        ];

        $serializedData = $this->json->serialize($data);
        if (!empty($data)) {
            $options['body'] = $serializedData;
        }

        try {
            if (self::LOG_DEBUG) {
                $this->logger->info($serializedData, [$unqId, $endpoint]);
            }

            $response = $this->guzzleClient->request($method, $endpoint, $options);
            $body = $response->getBody();
            $this->response = $this->json->unserialize($body);

            $this->logSuccess($method, $endpoint, $serializedData, $body, $unqId);

        } catch (GuzzleException $exception) {

            $this->logError($method, $endpoint, $serializedData, $exception, $unqId);

            $retries ++;
            if ($retries <= self::MAX_RETRIES && $exception->getCode() >= 500) {
                $this->logger->info(
                    __("Re-sending request. (%1/%2)", $retries, self::MAX_RETRIES),
                    [$unqId]
                );
                $this->sendRequest($method, $endpointSuffix, $data, $retries);
            } else {
                if ($exception->hasResponse()) {
                    $this->response = $this->json->unserialize((string)$exception->getResponse()->getBody());
                }
            }
        }

        return $this->response ?? [];
    }

    /**
     * Get api token from config
     *
     * @return string
     */
    private function getToken(): string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_API_TOKEN);
    }

    /**
     * Log successful transaction
     *
     * @param string $method
     * @param string $endpoint
     * @param string $request
     * @param string $response
     * @param string $unqId
     * @return void
     */
    private function logSuccess($method, $endpoint, $request, $response, $unqId): void
    {
        if (self::LOG_DEBUG) {
            $this->logger->info('SUCCESS', [
                $unqId,
                'response' => $this->json->unserialize((string)$response)
            ]);
        }

        $this->transactionLogHandler->logSuccessfulTransaction($method, $endpoint, $request, $response);
    }

    /**
     * Log error
     *
     * @param string $method
     * @param string $endpoint
     * @param string $request
     * @param GuzzleException $exception
     * @param string $unqId
     * @return void
     */
    private function logError($method, $endpoint, $request, $exception, $unqId)
    {
        $this->logger->error(
            __(
                "Returned an error %1: %2 %3",
                $exception->getCode(),
                $exception->getMessage(),
                $exception->getTraceAsString()
            ),
            [$unqId]
        );

        $this->transactionLogHandler->logFailedTransaction($method, $endpoint, $request, $exception);
    }
}
