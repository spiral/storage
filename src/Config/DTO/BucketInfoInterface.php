<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\OptionsBasedInterface;

interface BucketInfoInterface extends OptionsBasedInterface
{
    public const SERVER_KEY = 'server';

    public const DIRECTORY_KEY = 'directory';
    public const BUCKET_KEY = 'bucket';

    public function getServer(): string;

    public function setFileSystemInfo(FileSystemInfoInterface $fileSystemInfo): self;

    public function getFileSystemInfo(): ?FileSystemInfoInterface;
}
