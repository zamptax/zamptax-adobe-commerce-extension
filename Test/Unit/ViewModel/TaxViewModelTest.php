<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\ViewModel;

use ATF\Zamp\Services\Quote as QuoteService;
use ATF\Zamp\ViewModel\TaxViewModel;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\DesignInterface;
use Magento\Theme\Model\Theme;

class TaxViewModelTest extends TestCase
{
    /**
     * @var TaxViewModel
     */
    private $viewModel;

    /**
     * @var MockObject
     */
    private $designInterface;

    /**
     * @var MockObject
     */
    private $theme;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->designInterface = $this->createMock(DesignInterface::class);
        $this->theme = $this->createMock(Theme::class);
        $this->viewModel = new TaxViewModel(
            $this->designInterface,
            $this->theme
        );
    }

    /**
     * @return void
     */
    public function testTaxLabelWithZampTaxCalculatedForOrder(): void
    {
        $orderMock = $this->createMock(Order::class);
        $defaultTitle = 'Tax';

        $orderMock->expects($this->once())
            ->method('getData')
            ->with(QuoteService::IS_ZAMP_CALCULATED)
            ->willReturn(true);

        $expectedLabel = QuoteService::ZAMP_TAX_LABEL . ' ' . $defaultTitle;

        $this->assertEquals($expectedLabel, $this->viewModel->getTaxLabel($orderMock, $defaultTitle));
    }

    /**
     * @return void
     */
    public function testTaxLabelWithZampTaxNotCalculatedForOrder(): void
    {
        $orderMock = $this->createMock(Order::class);
        $defaultTitle = 'Tax';

        $orderMock->expects($this->once())
            ->method('getData')
            ->with(QuoteService::IS_ZAMP_CALCULATED)
            ->willReturn(false);

        $this->assertEquals($defaultTitle, $this->viewModel->getTaxLabel($orderMock, $defaultTitle));
    }

    /**
     * @return void
     */
    public function testTaxLabelWithZampTaxCalculatedForQuote(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $defaultTitle = 'Tax';

        $quoteMock->expects($this->once())
            ->method('getData')
            ->with(QuoteService::IS_ZAMP_CALCULATED)
            ->willReturn(true);

        $expectedLabel = QuoteService::ZAMP_TAX_LABEL . ' ' . $defaultTitle;

        $this->assertEquals($expectedLabel, $this->viewModel->getTaxLabel($quoteMock, $defaultTitle));
    }

    /**
     * @return void
     */
    public function testTaxLabelWithZampTaxNotCalculatedForQuote(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $defaultTitle = 'Tax';

        $quoteMock->expects($this->once())
            ->method('getData')
            ->with(QuoteService::IS_ZAMP_CALCULATED)
            ->willReturn(false);

        $this->assertEquals($defaultTitle, $this->viewModel->getTaxLabel($quoteMock, $defaultTitle));
    }
}
