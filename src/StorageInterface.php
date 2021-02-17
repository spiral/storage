<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;
use Spiral\StorageEngine\Exception\MountException;

interface StorageInterface extends StorageReaderInterface, StorageWriterInterface
{
    /**
     * Get concrete filesystem for specific operations
     *
     * @param string $key
     *
     * @return FilesystemOperator
     *
     * @throws MountException
     */
    public function getFileSystem(string $key): FilesystemOperator;

    /**
     * Get list of all mounted filesystems
     *
     * @return array
     */
    public function getFileSystemsNames(): array;
}
