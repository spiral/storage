<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;
use Spiral\StorageEngine\Exception\MountException;

interface StorageInterface extends StorageReaderInterface, StorageWriterInterface
{
    /**
     * @param string $key
     *
     * @return FilesystemOperator
     *
     * @throws MountException
     */
    public function getFileSystem(string $key): FilesystemOperator;

    public function getFileSystemsNames(): array;
}
