<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Controller\Adminhtml\TransactionLog;

use ATF\Zamp\Controller\Adminhtml\TransactionLog\View;
use ATF\Zamp\Model\ResourceModel\TransactionLog as TransactionLogResource;
use ATF\Zamp\Model\TransactionLog;
use ATF\Zamp\Model\TransactionLogFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends TestCase
{
    /**
     * @var View
     */
    protected $viewController;

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactoryMock;

    /**
     * @var Page|MockObject
     */
    protected $resultPageMock;

    /**
     * @var Config|MockObject
     */
    protected $pageConfigMock;

    /**
     * @var Title|MockObject
     */
    protected $pageTitleMock;

    /**
     * @var TransactionLogFactory|MockObject
     */
    protected $transactionLogFactoryMock;

    /**
     * @var TransactionLogResource|MockObject
     */
    protected $transactionLogResourceMock;

    /**
     * @var TransactionLog|MockObject
     */
    protected $transactionLogMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $request;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $resultRedirectFactoryMock;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManagerMock;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);

        $this->resultPageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();
        $this->pageConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTitleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionLogResourceMock = $this->createMock(TransactionLogResource::class);
        $this->transactionLogFactoryMock = $this->createMock(TransactionLogFactory::class);
        $this->transactionLogMock = $this->createMock(TransactionLog::class);

        $this->request = $this->getMockForAbstractClass(RequestInterface::class);

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

        $this->messageManagerMock = $this->getMockForAbstractClass(MessageManagerInterface::class);

        $contextMock->expects($this->any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $contextMock
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $contextMock
            ->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->viewController = new View(
            $contextMock,
            $this->resultPageFactoryMock,
            $this->transactionLogResourceMock,
            $this->transactionLogFactoryMock
        );
    }

    /**
     * Test execute with non-existing id
     */
    public function testExecuteWithInvalidId(): void
    {
        $this->request->expects($this->once())->method('getParam')->willReturn(1);
        $this->transactionLogFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->transactionLogMock);

        $this->transactionLogResourceMock
            ->expects($this->once())
            ->method('load')
            ->with($this->transactionLogMock, 1)
            ->willReturn($this->transactionLogMock);

        $this->transactionLogMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->messageManagerMock
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('This log no longer exists.'))
            ->willReturnSelf();

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/')
            ->willReturnSelf();

        $this->viewController->execute();
    }

    /**
     * Test execute with valid id
     */
    public function testExecuteWithValidId(): void
    {
        $this->request->expects($this->once())->method('getParam')->willReturn(1);
        $this->transactionLogFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->transactionLogMock);

        $this->transactionLogResourceMock
            ->expects($this->once())
            ->method('load')
            ->with($this->transactionLogMock, 1)
            ->willReturn($this->transactionLogMock);

        $this->transactionLogMock
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $this->resultPageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultPageMock);
        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->pageConfigMock);
        $this->pageConfigMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->once())
            ->method('prepend')
            ->with(__('Transaction Log %1', 1));

        $this->assertInstanceOf(
            Page::class,
            $this->viewController->execute()
        );
    }
}
