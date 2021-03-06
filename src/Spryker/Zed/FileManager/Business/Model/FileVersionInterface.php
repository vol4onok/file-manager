<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\FileManager\Business\Model;

interface FileVersionInterface
{
    /**
     * @param int|null $idFile
     *
     * @return int
     */
    public function getNewVersionNumber($idFile = null);

    /**
     * @param int $versionNumber
     *
     * @return string
     */
    public function getNewVersionName($versionNumber);
}
