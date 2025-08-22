<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model;

use ATF\Zamp\Model\Service\Calculation;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Calculate
{
    /**
     * @var TransactionObjectFactory
     */
    protected TransactionObjectFactory $transactionObjectFactory;

    /**
     * @var Calculation
     */
    protected Calculation $calculationService;

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var Json
     */
    protected Json $jsonSerializer;

    /**
     * @param TransactionObjectFactory $transactionObjectFactory
     * @param Calculation $calculationService
     * @param CheckoutSession $checkoutSession
     * @param Json $jsonSerializer
     */
    public function __construct(
        TransactionObjectFactory    $transactionObjectFactory,
        Calculation                 $calculationService,
        CheckoutSession             $checkoutSession,
        Json                        $jsonSerializer
    ) {
        $this->transactionObjectFactory = $transactionObjectFactory;
        $this->calculationService = $calculationService;
        $this->checkoutSession = $checkoutSession;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Execute Entry Point
     *
     * @param DataObject $request
     * @return array
     * @throws LocalizedException
     */
    public function execute(DataObject $request): array
    {
        $transactionObjModel = $this->transactionObjectFactory->create();
        $transactionObj = $transactionObjModel->createPayload($request);

        $payloadIdentifier = $this->parsePayloadIdentifier($transactionObj->toArray());

        $payloadHash = hash('sha256', $this->jsonSerializer->serialize($payloadIdentifier));

        $responsePool = $this->checkoutSession->getZampPayload();

        if ($responsePool && isset($responsePool[$payloadHash])) {
            $cachedResponse = $responsePool[$payloadHash];
            $response = $this->jsonSerializer->unserialize($cachedResponse);
        } else {
            $response = $this->calculationService->calculate($transactionObj->toArray());
            $responsePool[$payloadHash] = $this->jsonSerializer->serialize($response);
            $this->checkoutSession->setZampPayload($responsePool);
        }

        return $this->parseResponse($response);
    }

    /**
     * Parse Response
     *
     * @param array $response
     * @return array|array[]
     */
    public function parseResponse(array $response): array
    {
        if (!isset($response['taxDue']) || $response['taxDue'] === 0) {
            return [];
        }

        $result = [...$response];

        foreach ($result['lineItems'] as $key => $item) {
            $identifier = $item['productSku'] . '-' . $item['productTaxCode'];
            $rateId = $this->resolveRateId($response, $identifier);

            $result['lineItems'][$key]['rateId'] = $rateId;
            $result['lineItems'][$key]['rateTitle'] = $rateId;
        }

        return $result;
    }

    /**
     * Resolve Rate Id
     *
     * @param array $response
     * @param string $identifier
     * @return string
     */
    public function resolveRateId(array $response, string $identifier): string
    {
        $rateId = $identifier;
        if (isset($response['shipToAddress'])) {
            $shipToAddress = $response['shipToAddress'];

            $parts = [];
            foreach (['country', 'state', 'city'] as $code) {
                if (isset($shipToAddress[$code])) {
                    $parts[] = $shipToAddress[$code];
                }
            }
            $parts[] = $rateId;

            $rateId = implode('-', $parts);
        }

        return $rateId;
    }

    /**
     * Parse Payload Identifier for caching
     *
     * @param array $toArray
     * @return array
     */
    private function parsePayloadIdentifier(array $toArray): array
    {
        unset(
            $toArray['transactedAt'],
            $toArray['total'],
            $toArray['shipToAddress']['line1'],
            $toArray['shipToAddress']['line2'],
            $toArray['shipToAddress']['city'],
        );

        return $toArray;
    }
}
