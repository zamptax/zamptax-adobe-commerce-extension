<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Observer;

use ATF\Zamp\Model\QueueHandler;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;

class QueueCreditmemo implements ObserverInterface
{
    /**
     * @var QueueHandler
     */
    protected $queueHandler;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param QueueHandler $queueHandler
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        QueueHandler $queueHandler,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->queueHandler = $queueHandler;
        $this->orderRepository = $orderRepository;
    }
    
    /**
     * Queue creditmemo related to recently synced invoice
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $orderId = $event->getData('order_id');

        if ($orderId && $event->getData('transaction_type') === 'invoice') {
            try {
                $order = $this->orderRepository->get($orderId);
                $this->queueHandler->createCreditmemoQueue($order);
            } catch (NoSuchEntityException $e) {
                return;
            }
        }
    }
}
