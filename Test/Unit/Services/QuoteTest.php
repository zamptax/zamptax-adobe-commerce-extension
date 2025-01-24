<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Services;

use ATF\Zamp\Services\Quote;
use ATF\Zamp\Model\Configurations;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
    /**
     * @var Configurations|MockObject
     */
    private $zampConfigurations;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    /**
     * @var Quote
     */
    private $quoteService;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->zampConfigurations = $this->createMock(Configurations::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->connection = $this->createMock(AdapterInterface::class);

        $this->resourceConnection
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->quoteService = new Quote(
            $this->zampConfigurations,
            $this->resourceConnection,
            $this->logger
        );
    }

    /**
     * Test updatedCartQuote method with zamp calculation enabled
     *
     * @return void
     */
    public function testUpdatedCartQuoteWithZampCalculationEnabled()
    {
        $cartId = 1;

        $this->zampConfigurations
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(true);
        $this->zampConfigurations
            ->expects($this->once())
            ->method('isCalculationEnabled')
            ->willReturn(true);

        $this->connection
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->connection->getTableName('quote'),
                [Quote::IS_ZAMP_CALCULATED => 1],
                ['entity_id = ?' => $cartId]
            );

        $this->quoteService->updatedCartQuote($cartId);
    }

    /**
     * Test updatedCartQuote method with zamp calculation disabled
     *
     * @return void
     */
    public function testUpdatedCartQuoteWithZampCalculationDisabled()
    {
        $cartId = 1;

        $this->zampConfigurations
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(false);
        $this->zampConfigurations
            ->expects($this->never())
            ->method('isCalculationEnabled')
            ->willReturn(false);

        $this->connection
            ->expects($this->never())
            ->method('update');

        $this->quoteService->updatedCartQuote($cartId);
    }

    /**
     * Test updatedCartQuote method with exception
     *
     * @return void
     */
    public function testUpdatedCartQuoteHandlesException()
    {
        $cartId = 1;

        $this->zampConfigurations
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->willReturn(true);
        $this->zampConfigurations
            ->expects($this->once())
            ->method('isCalculationEnabled')
            ->willReturn(true);

        $this->connection
            ->expects($this->once())
            ->method('update')
            ->will($this->throwException(new \Exception('DB update error')));

        $this->logger
            ->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                [$this->stringContains('ATF\Zamp\Services\Quote::updatedCartQuote')],
                [$this->equalTo('DB update error')]
            );

        $this->quoteService->updatedCartQuote($cartId);
    }
}
