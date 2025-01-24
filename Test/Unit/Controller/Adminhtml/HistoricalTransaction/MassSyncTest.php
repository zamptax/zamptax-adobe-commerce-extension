<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Controller\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Controller\Adminhtml\HistoricalTransaction\MassSync;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\QueueHandler;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\FlagManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MassSyncTest extends TestCase
{
    /**
     * @var MassSync
     */
    private $massSyncController;

    /**
     * @var OrderCollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var Filter|MockObject
     */
    protected $filterMock;

    /**
     * @var QueueHandler|MockObject
     */
    protected $queueHandlerMock;

    /**
     * @var Configurations|MockObject
     */
    protected $configMock;

    /**
     * @var FlagManager|MockObject
     */
    protected $flagManagerMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $resultRedirectFactoryMock;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);

        $this->configMock = $this->createMock(Configurations::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->collectionFactoryMock = $this->createPartialMock(
            OrderCollectionFactory::class,
            ['create']
        );

        $this->filterMock = $this->getMockBuilder(Filter::class)
            ->onlyMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->queueHandlerMock = $this->getMockBuilder(QueueHandler::class)
            ->onlyMethods(['createQueue','getTotalQueued'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirectMock);

        $contextMock->expects($this->any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $this->massSyncController = new MassSync(
            $contextMock,
            $this->collectionFactoryMock,
            $this->filterMock,
            $this->configMock,
            $this->queueHandlerMock,
            $this->flagManagerMock
        );
    }

    /**
     * Test massSync with config disabled
     */
    public function testSyncDisabled(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(true);
        $this->configMock
            ->expects($this->once())
            ->method('isSendTransactionsEnabled')
            ->willReturn(false);

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*');

        $this->massSyncController->execute();
    }

    /**
     * Test MassSync with config enabled
     */
    public function testSyncEnabled(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(true);
        $this->configMock
            ->expects($this->once())
            ->method('isSendTransactionsEnabled')
            ->willReturn(true);

        $collectionMock = $this->createMock(Collection::class);

        $this->collectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $orderId = 1;
        $order = $this->createMock(Order::class);

        $order->expects($this->once())->method('getId')->willReturn($orderId);

        $collectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$order]));

        $this->filterMock
            ->expects($this->once())
            ->method('getCollection')
            ->with($collectionMock)
            ->willReturn($collectionMock);

        $this->queueHandlerMock
            ->expects($this->atLeastOnce())
            ->method('createQueue')
            ->with($orderId);
        $this->queueHandlerMock
            ->expects($this->once())
            ->method('getTotalQueued')
            ->willReturn(1);
        $this->flagManagerMock
            ->expects($this->once())
            ->method('saveFlag')
            ->with('atf_zamp_dismissed_messages', 0);

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/queue');

        $this->massSyncController->execute();
    }
}
