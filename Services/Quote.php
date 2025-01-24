<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Services;

use ATF\Zamp\Model\Configurations;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;

class Quote
{
    public const IS_ZAMP_CALCULATED = 'is_zamp_tax_calculated';
    public const ZAMP_TAX_LABEL = 'Sales';

    /**
     * @var Configurations
     */
    private $zampConfigurations;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @param Configurations $zampConfigurations
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Configurations $zampConfigurations,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->zampConfigurations = $zampConfigurations;
        $this->connection = $resource->getConnection();
        $this->logger = $logger;
    }

    /**
     * Updated quote object
     *
     * @param mixed $cartId
     * @return void
     */
    public function updatedCartQuote(mixed $cartId): void
    {
        $doZampCalc = $this->zampConfigurations->isModuleEnabled()
            && $this->zampConfigurations->isCalculationEnabled();

        if ($doZampCalc) {
            try {
                $this->connection->update(
                    $this->connection->getTableName('quote'),
                    [self::IS_ZAMP_CALCULATED => 1],
                    ['entity_id = ?' => (int) $cartId]
                );
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__);
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Check if current cart is zamp calculated
     *
     * @param mixed $cartId
     * @return int
     */
    public function isZampCalculated(mixed $cartId): int
    {
        $result = 0;
        $quoteId = is_string($cartId) ? $this->getQuoteIdByCartId($cartId) : $cartId;

        if ($quoteId) {
            $select = $this->connection
                ->select()
                ->from(
                    $this->connection->getTableName('quote'),
                    [self::IS_ZAMP_CALCULATED]
                )
                ->where('entity_id = ?', (int) $quoteId);

            if ($isZampCalculated = $this->connection->fetchOne($select)) {
                $result = (int) $isZampCalculated;
            }
        }

        return $result;
    }

    /**
     * Check if current cart is zamp calculated
     *
     * @param mixed $cartId
     * @return int
     */
    public function getQuoteIdByCartId(mixed $cartId): int
    {
        $select = $this->connection
            ->select()
            ->from(
                $this->connection->getTableName('quote_id_mask'),
                ['quote_id']
            )
            ->where('masked_id = ?', $cartId);

        return (int) ($this->connection->fetchOne($select) ?? 0);
    }
}
