<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\FileManager\Business\Model;

use Generated\Shared\Transfer\FileSystemContentTransfer;
use Generated\Shared\Transfer\FileSystemDeleteTransfer;
use Generated\Shared\Transfer\FileSystemQueryTransfer;
use Spryker\Zed\FileManager\Dependency\Service\FileManagerToFileSystemBridgeInterface;
use Spryker\Zed\FileManager\FileManagerConfig;

class FileContent implements FileContentInterface
{
    /**
     * @var \Spryker\Zed\FileManager\Dependency\Service\FileManagerToFileSystemBridgeInterface
     */
    protected $fileSystemService;

    /**
     * @var \Spryker\Zed\FileManager\FileManagerConfig
     */
    private $config;

    /**
     * @param \Spryker\Zed\FileManager\Dependency\Service\FileManagerToFileSystemBridgeInterface $fileSystemService
     * @param \Spryker\Zed\FileManager\FileManagerConfig $config
     */
    public function __construct(FileManagerToFileSystemBridgeInterface $fileSystemService, FileManagerConfig $config)
    {
        $this->fileSystemService = $fileSystemService;
        $this->config = $config;
    }

    /**
     * @param string $fileName
     * @param string $content
     *
     * @return void
     */
    public function save($fileName, $content)
    {
        $fileSystemContentTransfer = new FileSystemContentTransfer();
        $fileSystemContentTransfer->setFileSystemName($this->config->getStorageName());
        $fileSystemContentTransfer->setPath($fileName);
        $fileSystemContentTransfer->setContent($content);

        $this->fileSystemService->put($fileSystemContentTransfer);
    }

    /**
     * @param string $fileName
     *
     * @return void
     */
    public function delete($fileName)
    {
        $fileSystemDeleteTransfer = new FileSystemDeleteTransfer();
        $fileSystemDeleteTransfer->setFileSystemName($this->config->getStorageName());
        $fileSystemDeleteTransfer->setPath($fileName);

        $this->fileSystemService->delete($fileSystemDeleteTransfer);
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public function read($fileName)
    {
        $fileSystemQueryTransfer = new FileSystemQueryTransfer();
        $fileSystemQueryTransfer->setFileSystemName($this->config->getStorageName());
        $fileSystemQueryTransfer->setPath($fileName);

        return $this->fileSystemService->read($fileSystemQueryTransfer);
    }
}
