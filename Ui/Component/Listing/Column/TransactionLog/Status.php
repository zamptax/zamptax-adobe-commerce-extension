<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Ui\Component\Listing\Column\TransactionLog;

use ATF\Zamp\Model\HistoricalTransactionSyncQueue;
use ATF\Zamp\Model\TransactionLog;
use Magento\Ui\Component\Listing\Columns\Column;

class Status extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $labels = TransactionLog::getStatusOptionArray();
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $value = $item[$this->getData('name')];
                $label = $labels[$value] ?: '';
                $item[$this->getData('name')] = $label;
            }
        }

        return $dataSource;
    }
}
