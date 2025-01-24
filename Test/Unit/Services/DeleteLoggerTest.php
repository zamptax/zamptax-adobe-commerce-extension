<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Test\Unit\Services;

use ATF\Zamp\Logger\Handler;
use ATF\Zamp\Services\DeleteLogger;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeleteLoggerTest extends TestCase
{
    /**
     * @var DeleteLogger
     */
    protected $service;

    /**
     * @var WriteInterface|MockObject
     */
    protected $writer;

    /**
     * @var Handler|MockObject
     */
    protected $loggerHandler;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var File|MockObject
     */
    protected $file;

    protected function setUp(): void
    {
        $this->writer = $this->createMock(WriteInterface::class);
        $fileSystem = $this->createMock(FileSystem::class);
        $fileSystem->expects($this->any())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::LOG)
            ->willReturn($this->writer);
        $this->loggerHandler = $this->createMock(Handler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->file = $this->createMock(File::class);

        $this->service = new DeleteLogger($fileSystem, $this->loggerHandler, $this->file, $this->logger);
    }

    /**
     * Test execute method
     */
    public function testExecute(): void
    {
        $basename = 'test.log';
        $path = '/var/log/' . $basename;

        $this->loggerHandler
            ->expects($this->once())
            ->method('__debugInfo')
            ->willReturn(["fileName" => $path]);
        $this->file
            ->expects($this->once())
            ->method('getPathInfo')
            ->with($path)
            ->willReturn(['basename' => $basename]);
        $this->writer
            ->expects($this->once())
            ->method('delete')
            ->with($basename)
            ->willReturn(true);

        $this->service->execute();
    }
}
