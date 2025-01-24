<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Services;

use ATF\Zamp\Logger\Handler;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Psr\Log\LoggerInterface;

class DeleteLogger
{
    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $logDirectory;

    /**
     * @var Handler
     */
    private $loggerHandler;

    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Filesystem $filesystem
     * @param Handler $loggerHandler
     * @param File $file
     * @param LoggerInterface $logger
     */
    public function __construct(
        Filesystem $filesystem,
        Handler $loggerHandler,
        File $file,
        LoggerInterface $logger
    ) {
        $this->logDirectory = $filesystem->getDirectoryWrite(DirectoryList::LOG);
        $this->loggerHandler = $loggerHandler;
        $this->file = $file;
        $this->logger = $logger;
    }

    /**
     * Delete custom logger
     *
     * @return void
     */
    public function execute()
    {
        $logger = $this->getLoggerFilename();

        if (!empty($logger)) {
            try {
                $this->logDirectory->delete($logger);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Get logger filename
     *
     * @return string
     */
    protected function getLoggerFilename()
    {
        $info = $this->loggerHandler->__debugInfo();
        $pathInfo = $this->file->getPathInfo($info['fileName']);

        return $pathInfo['basename'] ?? '';
    }
}
