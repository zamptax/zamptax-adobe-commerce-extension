<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Test\Unit\Controller\Adminhtml\HistoricalTransaction;

use ATF\Zamp\Controller\Adminhtml\HistoricalTransaction\DismissMessage;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\FlagManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DismissMessageTest extends TestCase
{
    /**
     * @var DismissMessage
     */
    private $controller;

    /**
     * @var FlagManager|MockObject
     */
    protected $flagManagerMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var RedirectInterface|MockObject
     */
    protected $redirectMock;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $responseMock;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var ActionFlag|MockObject
     */
    protected $actionFlagMock;

    /**
     * @var Data|MockObject
     */
    protected $helper;

    protected function setUp(): void
    {
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->contextMock = $this->createMock(Context::class);

        $this->redirectMock = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(['setRedirect'])
            ->getMockForAbstractClass();

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['setIsUrlNotice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionFlagMock = $this->getMockBuilder(ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->createPartialMock(Data::class, ['getUrl']);

        $this->contextMock->expects($this->any())
            ->method('getRedirect')
            ->willReturn($this->redirectMock);

        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->contextMock->expects($this->any())
            ->method('getSession')
            ->willReturn($this->sessionMock);

        $this->contextMock->expects($this->any())
            ->method('getActionFlag')
            ->willReturn($this->actionFlagMock);

        $this->contextMock->expects($this->any())
            ->method('getHelper')
            ->willReturn($this->helper);

        $this->controller = new DismissMessage($this->contextMock, $this->flagManagerMock);
    }

    /**
     * Test DismissMessage controller
     */
    public function testExecute(): void
    {
        $this->flagManagerMock
            ->expects($this->once())
            ->method('saveFlag')
            ->with('atf_zamp_dismissed_messages', 1)
            ->willReturn(true);

        $this->redirectMock->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn('referer_url');

        $this->actionFlagMock->expects($this->any())->method('get')
            ->with('', 'check_url_settings')
            ->willReturn(true);

        $this->sessionMock
            ->expects($this->any())
            ->method('setIsUrlNotice')
            ->with(true);

        $this->helper->expects($this->any())->method("getUrl")->willReturn("magento.com");

        $this->responseMock
            ->expects($this->any())
            ->method('setRedirect')
            ->willReturn(1);

        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->controller->execute()
        );
    }
}
