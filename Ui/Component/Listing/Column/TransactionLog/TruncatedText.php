<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Ui\Component\Listing\Column\TransactionLog;

use Magento\Ui\Component\Listing\Columns\Column;

class TruncatedText extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $value = $item[$this->getData('name')];
                $item[$this->getData('name')] = strlen($value) > 100
                    ? substr($value, 0, 100)."..."
                    : $value;
            }
        }

        return $dataSource;
    }
}
