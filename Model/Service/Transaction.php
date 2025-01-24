<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Service;

/**
 * Sends transaction request to zamp
 *
 * @see https://developer.zamp.com/api/transactions
 */
class Transaction extends Request
{
    public const TRANSACTION_URL = parent::API_URL . '/transactions';

    public const RESPONSE_CODE_CONFLICT = 'CONFLICT';

    /**
     * Get endpoint url
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return static::TRANSACTION_URL;
    }

    /**
     * Post a transaction to Zamp
     *
     * @param array $transactionData
     * @return array
     */
    public function createTransaction(array $transactionData): array
    {
        return $this->sendRequest('POST', '', $transactionData);
    }

    /**
     * Retrieve a transaction from Zamp
     *
     * @param string $transactionId
     * @return array
     */
    public function retrieveTransaction(string $transactionId): array
    {
        return $this->sendRequest('GET', $transactionId);
    }

    /**
     * Create a refund transaction
     *
     * @param array $transactionData
     * @return array
     */
    public function createRefundTransaction(array $transactionData): array
    {
        return $this->sendRequest('POST', '', $transactionData);
    }

    /**
     * List all transactions
     *
     * @param int $limit
     * @param string|null $cursor
     * @return array
     */
    public function listTransactions(int $limit = 10, string $cursor = null): array
    {
        $data['limit'] = $limit;
        if ($cursor) {
            $data['cursor'] = $cursor;
        }

        $params = '?' . http_build_query($data);

        return $this->sendRequest('GET', $params, $data);
    }
}
