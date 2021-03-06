<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\FileManager\Business\Model;

class FileVersion implements FileVersionInterface
{
    const DEFAULT_VERSION_NUMBER = 1;

    /**
     * @var \Spryker\Zed\FileManager\Business\Model\FileFinderInterface
     */
    private $fileFinder;

    /**
     * @param \Spryker\Zed\FileManager\Business\Model\FileFinderInterface $fileFinder
     */
    public function __construct(FileFinderInterface $fileFinder)
    {
        $this->fileFinder = $fileFinder;
    }

    /**
     * @param int|null $idFile
     *
     * @return int
     */
    public function getNewVersionNumber($idFile = null)
    {
        if ($idFile === null) {
            return static::DEFAULT_VERSION_NUMBER;
        }
        $fileInfo = $this->fileFinder->getLatestFileInfoByFkFile($idFile);

        if ($fileInfo === null) {
            return static::DEFAULT_VERSION_NUMBER;
        }

        return $fileInfo->getVersion() + 1;
    }

    /**
     * @param int $versionNumber
     *
     * @return string
     */
    public function getNewVersionName($versionNumber)
    {
        return sprintf('v. %d', $versionNumber);
    }
}
