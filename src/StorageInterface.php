<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;

interface StorageInterface extends StorageReaderInterface, StorageWriterInterface
{
    public function mountFilesystem(string $key, FilesystemOperator $filesystem): void;

    public function isFileSystemExists(string $key): bool;

    public function getFileSystem(string $key): ?FilesystemOperator;

    public function extractMountedFileSystemsKeys(): array;
}
