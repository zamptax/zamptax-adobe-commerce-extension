<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Plugin\Framework\UiComponent\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use ATF\Zamp\Model\Configurations;

class HistoricalTransactionProvider
{
    protected const DATA_PROVIDER_NAME = 'zamp_historical_transaction_sync_listing_data_source';

    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @param Configurations $config
     * @param TimezoneInterface $timeZone
     */
    public function __construct(
        Configurations $config,
        TimezoneInterface $timeZone
    ) {
        $this->config = $config;
        $this->timeZone = $timeZone;
    }

    /**
     * Append the earliest date to sync to historical transaction config data.
     *
     * @param DataProvider $subject
     * @param array $config
     * @return array
     */
    public function afterGetConfigData(DataProvider $subject, array $config)
    {
        if ($subject->getName() === self::DATA_PROVIDER_NAME) {
            $config['earliestDateToSync'] = $this->config->getEarliestDateToSync()->format('m/d/Y');
        }

        return $config;
    }

    /**
     * Fix for issue caused by created_at field
     *
     * @see Magento\Sales\Plugin\Model\ResourceModel\Order\OrderGridCollectionFilter
     * @see https://github.com/magento/magento2/issues/38916
     *
     * @param DataProvider $subject
     * @param Filter $filter
     * @return Filter[]
     */
    public function beforeAddFilter(DataProvider $subject, Filter $filter)
    {
        if ($subject->getName() === self::DATA_PROVIDER_NAME && $filter->getField() === 'created_at') {
            $filter->setField('main_table.created_at');
            if ($value = $this->isValidDate($filter->getValue())) {
                $value->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                $filter->setValue($value);
            }
        }

        return [$filter];
    }

    /**
     * Validate date string
     *
     * @param mixed $datetime
     * @return mixed
     */
    private function isValidDate(mixed $datetime): mixed
    {
        try {
            return $datetime instanceof \DateTimeInterface
                ? $datetime : (is_string($datetime)
                    ? new \DateTime($datetime, new \DateTimeZone($this->timeZone->getConfigTimezone())) : false);
        } catch (\Exception $e) {
            return false;
        }
    }
}
