<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Controller\Adminhtml\Config;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filter\StripTags;
use ATF\Zamp\Model\Service\Transaction;
use ATF\Zamp\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

class TestConnection implements ActionInterface, HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StripTags
     */
    private $tagFilter;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param StripTags $tagFilter
     * @param Transaction $transaction
     * @param Logger $logger
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        RequestInterface $request,
        StripTags $tagFilter,
        Transaction $transaction,
        Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->tagFilter = $tagFilter;
        $this->transaction = $transaction;
        $this->logger = $logger;
    }

    /**
     * Check connection to API
     *
     * @return Json
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'errorMessage' => '',
        ];

        try {
            $options = $this->sanitizeRequestData($this->request->getParams());

            if (empty($options['api_secret'])) {
                throw new LocalizedException(__('API Token is required.'));
            }
            if ($this->isTokenValid()) {
                $result['success'] = true;
            }
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->error('Error in TestConnection: ' . $e->getMessage());
            $result['errorMessage'] = __('An error occurred while testing the connection.');
        }

        return $this->resultJsonFactory->create()->setData($result);
    }

    /**
     * Sanitize request data to prevent potential security issues
     *
     * @param array $data
     * @return array
     */
    private function sanitizeRequestData(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = $this->tagFilter->filter($value);
        }
        return $sanitized;
    }

    /**
     * Check if the API token is valid
     *
     * @return bool
     */
    private function isTokenValid(): bool
    {
        $transaction = $this->transaction->listTransactions();
        if (isset($transaction['data'])) {
            return true;
        }

        return false;
    }
}
