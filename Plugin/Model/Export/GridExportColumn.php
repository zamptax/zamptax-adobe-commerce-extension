<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Plugin\Model\Export;

use ATF\Zamp\Model\HistoricalTransactionSyncQueue;
use ATF\Zamp\Model\TransactionLog;
use Exception;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Model\Export\MetadataProvider;
use Magento\Ui\Component\MassAction\Filter;

class GridExportColumn
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @param Filter $filter
     */
    public function __construct(
        Filter $filter
    ) {
        $this->filter = $filter;
    }

    /**
     * Add header columns to result
     *
     * @param MetadataProvider $subject
     * @param array $result
     * @param UiComponentInterface $component
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws Exception
     */
    public function afterGetHeaders(
        MetadataProvider $subject,
        array $result,
        UiComponentInterface $component
    ): array {
        $namespace = $component->getContext()->getNamespace();
        if ($namespace === 'zamp_historical_transaction_queue_listing') {
            $result[] = __('Request');
            $result[] = __('Response');
            $result[] = __('Invoice ID');
            $result[] = __('Creditmemo ID');
        }
        return $result;
    }

    /**
     * Add columns to result
     *
     * @param MetadataProvider $subject
     * @param array $result
     * @param UiComponentInterface $component
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws Exception
     */
    public function afterGetFields(
        MetadataProvider $subject,
        array $result,
        UiComponentInterface $component
    ): array {
        $namespace = $component->getContext()->getNamespace();
        if ($namespace === 'zamp_historical_transaction_queue_listing') {
            $result[] = 'body_request';
            $result[] = 'response_data';
            $result[] = 'invoice_id';
            $result[] = 'creditmemo_id';
        }
        return $result;
    }

    /**
     * Update status value to label
     *
     * @param MetadataProvider $subject
     * @param array $result
     * @param DocumentInterface $document
     * @param array $fields
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws Exception
     */
    public function afterGetRowData(
        MetadataProvider $subject,
        array $result,
        DocumentInterface $document,
        $fields
    ): array {
        $component = $this->filter->getComponent();
        $namespace = $component->getContext()->getNamespace();
        if ($namespace === 'zamp_historical_transaction_queue_listing'
            || $namespace === 'zamp_transaction_log_listing'
        ) {
            $statusLabels = $this->getStatusLabels($namespace);
            foreach ($fields as $index => $column) {
                if ($column === 'status') {
                    $result[$index] = $statusLabels[$result[$index]];
                }
            }
        }
        return $result;
    }

    /**
     * Get status labels
     *
     * @param string $namespace
     * @return array
     */
    protected function getStatusLabels($namespace)
    {
        $labels = [];
        if ($namespace === 'zamp_historical_transaction_queue_listing') {
            $labels = HistoricalTransactionSyncQueue::getStatusOptionArray();
        }

        if ($namespace === 'zamp_transaction_log_listing') {
            $labels = TransactionLog::getStatusOptionArray();
        }

        return $labels;
    }
}
