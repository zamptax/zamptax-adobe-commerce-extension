<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Service;

/**
 * Calculations requests
 *
 * @see https://developer.zamp.com/api/calculations
 */
class Calculation extends Request
{
    public const CALCULATIONS_URL = parent::API_URL . '/calculations';

    /**
     * Get endpoint url
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return static::CALCULATIONS_URL;
    }

    /**
     * Calculate taxes
     *
     * @param array $transactionData
     * @return array
     */
    public function calculate(array $transactionData): array
    {
        return $this->sendRequest('POST', '', $transactionData);
    }
}
