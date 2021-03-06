<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\FileManager\Business\Model;

use Orm\Zed\FileManager\Persistence\SpyFileInfo;
use Spryker\Zed\FileManager\Exception\FileInfoNotFoundException;
use Spryker\Zed\FileManager\Exception\FileNotFoundException;

class FileRollback implements FileRollbackInterface
{
    /**
     * @var \Spryker\Zed\FileManager\Business\Model\FileVersionInterface
     */
    protected $fileVersion;

    /**
     * @var \Spryker\Zed\FileManager\Business\Model\FileFinderInterface
     */
    protected $fileFinder;

    /**
     * FileSaver constructor.
     *
     * @param \Spryker\Zed\FileManager\Business\Model\FileFinderInterface $fileFinder
     * @param \Spryker\Zed\FileManager\Business\Model\FileVersionInterface $fileVersion
     */
    public function __construct(FileFinderInterface $fileFinder, FileVersionInterface $fileVersion)
    {
        $this->fileVersion = $fileVersion;
        $this->fileFinder = $fileFinder;
    }

    /**
     * @param int $idFile
     * @param int $idFileInfo
     *
     * @return void
     */
    public function rollback($idFile, $idFileInfo)
    {
        $file = $this->getFile($idFile);
        $targetFileInfo = $this->getFileInfo($idFileInfo);
        $file->addSpyFileInfo($this->createNewFileInfo($targetFileInfo));
    }

    /**
     * @param \Orm\Zed\FileManager\Persistence\SpyFileInfo $targetFileInfo
     *
     * @return \Orm\Zed\FileManager\Persistence\SpyFileInfo
     */
    protected function createNewFileInfo(SpyFileInfo $targetFileInfo)
    {
        $fileInfo = new SpyFileInfo();
        $fileInfo->fromArray($targetFileInfo->toArray());

        $this->updateVersion($fileInfo, $targetFileInfo->getFkFile());
        $fileInfo->save();

        return $fileInfo;
    }

    /**
     * @param \Orm\Zed\FileManager\Persistence\SpyFileInfo $fileInfo
     * @param int $idFile
     *
     * @return void
     */
    protected function updateVersion(SpyFileInfo $fileInfo, $idFile)
    {
        $newVersion = $this->fileVersion->getNewVersionNumber($idFile);
        $newVersionName = $this->fileVersion->getNewVersionName($newVersion);
        $fileInfo->setVersion($newVersion);
        $fileInfo->setVersionName($newVersionName);
    }

    /**
     * @param int $idFile
     *
     * @throws \Spryker\Zed\FileManager\Exception\FileNotFoundException
     *
     * @return \Orm\Zed\FileManager\Persistence\SpyFile
     */
    protected function getFile($idFile)
    {
        $file = $this->fileFinder->getFile($idFile);

        if ($file == null) {
            throw new FileNotFoundException(sprintf('File with id %s not found', $idFile));
        }

        return $file;
    }

    /**
     * @param int $idFileInfo
     *
     * @throws \Spryker\Zed\FileManager\Exception\FileInfoNotFoundException
     *
     * @return \Orm\Zed\FileManager\Persistence\SpyFileInfo
     */
    protected function getFileInfo($idFileInfo)
    {
        $fileInfo = $this->fileFinder->getFileInfo($idFileInfo);

        if ($fileInfo == null) {
            throw new FileInfoNotFoundException(sprintf('Target file info with id %s not found', $idFileInfo));
        }

        return $fileInfo;
    }
}
