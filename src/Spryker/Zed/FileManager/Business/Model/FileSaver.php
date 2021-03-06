<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\FileManager\Business\Model;

use Exception;
use Generated\Shared\Transfer\FileManagerSaveRequestTransfer;
use Orm\Zed\FileManager\Persistence\SpyFile;
use Orm\Zed\FileManager\Persistence\SpyFileInfo;
use Spryker\Zed\FileManager\FileManagerConfig;
use Spryker\Zed\FileManager\Persistence\FileManagerQueryContainerInterface;

class FileSaver implements FileSaverInterface
{
    /**
     * @var \Spryker\Zed\FileManager\Persistence\FileManagerQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var \Spryker\Zed\FileManager\Business\Model\FileVersionInterface
     */
    protected $fileVersion;

    /**
     * @var \Spryker\Zed\FileManager\Business\Model\FileFinderInterface
     */
    protected $fileFinder;

    /**
     * @var \Spryker\Zed\FileManager\Business\Model\FileContentInterface
     */
    protected $fileContent;

    /**
     * @var \Spryker\Zed\FileManager\FileManagerConfig
     */
    protected $config;

    /**
     * @var \Spryker\Zed\FileManager\Business\Model\FileLocalizedAttributesSaverInterface
     */
    private $attributesSaver;

    /**
     * FileSaver constructor.
     *
     * @param \Spryker\Zed\FileManager\Persistence\FileManagerQueryContainerInterface $queryContainer
     * @param \Spryker\Zed\FileManager\Business\Model\FileVersionInterface $fileVersion
     * @param \Spryker\Zed\FileManager\Business\Model\FileFinderInterface $fileFinder
     * @param \Spryker\Zed\FileManager\Business\Model\FileContentInterface $fileContent
     * @param \Spryker\Zed\FileManager\Business\Model\FileLocalizedAttributesSaverInterface $attributesSaver
     * @param \Spryker\Zed\FileManager\FileManagerConfig $config
     */
    public function __construct(
        FileManagerQueryContainerInterface $queryContainer,
        FileVersionInterface $fileVersion,
        FileFinderInterface $fileFinder,
        FileContentInterface $fileContent,
        FileLocalizedAttributesSaverInterface $attributesSaver,
        FileManagerConfig $config
    ) {
        $this->queryContainer = $queryContainer;
        $this->fileVersion = $fileVersion;
        $this->fileFinder = $fileFinder;
        $this->fileContent = $fileContent;
        $this->config = $config;
        $this->attributesSaver = $attributesSaver;
    }

    /**
     * @param \Generated\Shared\Transfer\FileManagerSaveRequestTransfer $saveRequestTransfer
     *
     * @return int
     */
    public function save(FileManagerSaveRequestTransfer $saveRequestTransfer)
    {
        if ($this->checkFileExists($saveRequestTransfer)) {
            return $this->update($saveRequestTransfer);
        }

        return $this->create($saveRequestTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\FileManagerSaveRequestTransfer $saveRequestTransfer
     *
     * @return int
     */
    protected function update(FileManagerSaveRequestTransfer $saveRequestTransfer)
    {
        $file = $this->fileFinder->getFile($saveRequestTransfer->getFile()->getIdFile());

        return $this->saveFile($file, $saveRequestTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\FileManagerSaveRequestTransfer $saveRequestTransfer
     *
     * @return int
     */
    protected function create(FileManagerSaveRequestTransfer $saveRequestTransfer)
    {
        $file = new SpyFile();

        return $this->saveFile($file, $saveRequestTransfer);
    }

    /**
     * @param \Orm\Zed\FileManager\Persistence\SpyFile $file
     * @param \Generated\Shared\Transfer\FileManagerSaveRequestTransfer $saveRequestTransfer
     *
     * @throws \Exception
     *
     * @return int
     */
    protected function saveFile(SpyFile $file, FileManagerSaveRequestTransfer $saveRequestTransfer)
    {
        $this->queryContainer->getConnection()->beginTransaction();

        try {
            $file->fromArray($saveRequestTransfer->getFile()->toArray());

            $fileInfo = $this->createFileInfo($saveRequestTransfer);
            $this->addFileInfoToFile($file, $fileInfo);

            $savedRowsCount = $file->save();
            $this->attributesSaver->saveFileLocalizedAttributes($file, $saveRequestTransfer);
            $this->saveContent($saveRequestTransfer, $file, $fileInfo);

            $this->queryContainer->getConnection()->commit();

            return $savedRowsCount;
        } catch (Exception $exception) {
            $this->queryContainer->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param \Orm\Zed\FileManager\Persistence\SpyFile $file
     * @param \Orm\Zed\FileManager\Persistence\SpyFileInfo|null $fileInfo
     *
     * @return void
     */
    protected function addFileInfoToFile(SpyFile $file, SpyFileInfo $fileInfo = null)
    {
        if ($fileInfo !== null) {
            $file->addSpyFileInfo($fileInfo);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\FileManagerSaveRequestTransfer $saveRequestTransfer
     * @param \Orm\Zed\FileManager\Persistence\SpyFile $file
     * @param \Orm\Zed\FileManager\Persistence\SpyFileInfo|null $fileInfo
     *
     * @return void
     */
    protected function saveContent(FileManagerSaveRequestTransfer $saveRequestTransfer, SpyFile $file, SpyFileInfo $fileInfo = null)
    {
        if ($saveRequestTransfer->getContent() !== null || $fileInfo !== null) {
            $newFileName = $this->getNewFileName($file->getFileName(), $fileInfo->getVersionName());
            $this->fileContent->save($newFileName, $saveRequestTransfer->getContent());
            $this->addStorageInfo($fileInfo, $newFileName);
        }
    }

    /**
     * @param \Orm\Zed\FileManager\Persistence\SpyFileInfo $fileInfo
     * @param string $newFileName
     *
     * @return void
     */
    protected function addStorageInfo(SpyFileInfo $fileInfo, string $newFileName)
    {
        $fileInfo->reload();
        $fileInfo->setStorageName($this->config->getStorageName());
        $fileInfo->setStorageFileName($newFileName);

        $fileInfo->save();
    }

    /**
     * @param \Generated\Shared\Transfer\FileManagerSaveRequestTransfer $saveRequestTransfer
     *
     * @return \Orm\Zed\FileManager\Persistence\SpyFileInfo
     */
    protected function createFileInfo(FileManagerSaveRequestTransfer $saveRequestTransfer)
    {
        if ($saveRequestTransfer->getContent() === null) {
            return null;
        }

        $fileInfoTransfer = $saveRequestTransfer->getFileInfo();
        $fileInfo = new SpyFileInfo();
        $fileInfo->fromArray($fileInfoTransfer->toArray());

        $newVersion = $this->fileVersion->getNewVersionNumber($fileInfoTransfer->getFkFile());
        $newVersionName = $this->fileVersion->getNewVersionName($newVersion);
        $fileInfo->setVersion($newVersion);
        $fileInfo->setVersionName($newVersionName);

        return $fileInfo;
    }

    /**
     * @param string $fileName
     * @param string $versionName
     *
     * @return string
     */
    protected function getNewFileName(string $fileName, string $versionName)
    {
        $fileNameVersionDelimiter = $this->config->getFileNameVersionDelimiter();

        return $fileName . $fileNameVersionDelimiter . $versionName;
    }

    /**
     * @param \Generated\Shared\Transfer\FileManagerSaveRequestTransfer $saveRequestTransfer
     *
     * @return bool
     */
    protected function checkFileExists(FileManagerSaveRequestTransfer $saveRequestTransfer)
    {
        $fileId = $saveRequestTransfer->getFile()->getIdFile();

        if ($fileId == null) {
            return false;
        }

        $file = $this->fileFinder->getFile($fileId);

        return $file !== null;
    }
}
