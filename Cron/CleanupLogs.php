<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Cron;

use ATF\Zamp\Logger\Logger;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\ResourceModel\TransactionLog as TransactionLogResource;
use ATF\Zamp\Model\ResourceModel\TransactionLog\CollectionFactory;
use ATF\Zamp\Model\System\Config\Backend\LogLifetime;

class CleanupLogs
{
    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var TransactionLogResource
     */
    protected $resource;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Configurations $config
     * @param CollectionFactory $collectionFactory
     * @param TransactionLogResource $resource
     * @param Logger $logger
     */
    public function __construct(
        Configurations $config,
        CollectionFactory $collectionFactory,
        TransactionLogResource $resource,
        Logger $logger
    ) {
        $this->config = $config;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Transaction log cleanup
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->config->isLoggingEnabled()) {
            return;
        }

        $lifetime = $this->config->getLogLifetime() ?? LogLifetime::MINIMUM_LIFETIME_IN_DAYS;
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $duration = 'P' . $lifetime . 'D';
        $oldest = $now->sub(new \DateInterval($duration))->format('Y-m-d 00:00:00');

        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('created_at', ['lt' => $oldest]);

            $size = $collection->getSize();

            foreach ($collection as $log) {
                $this->resource->delete($log);
            }

            $this->logger->info(__('A total of %1 log(s) have been deleted.', $size));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
