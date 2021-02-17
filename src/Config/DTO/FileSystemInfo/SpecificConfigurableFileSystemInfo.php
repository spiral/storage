<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\FileSystemInfo;

use Spiral\StorageEngine\Exception\StorageException;

interface SpecificConfigurableFileSystemInfo
{
    /**
     * Construct specific DTO parts by info
     *
     * @param array $info
     *
     * @throws StorageException
     */
    public function constructSpecific(array $info): void;
}
