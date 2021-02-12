<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;

interface BucketInfoInterface
{
    public const DIRECTORY_KEY = 'directory';
    public const SERVER_KEY = 'server';

    public function getDirectory(): ?string;

    public function getServer(): string;

    public function setFileSystemInfo(FileSystemInfoInterface $fileSystemInfo): self;

    public function getFileSystemInfo(): ?FileSystemInfoInterface;
}
