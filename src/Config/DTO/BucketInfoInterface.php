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

    /**
     * Get bucket server key
     *
     * @return string
     */
    public function getServer(): string;

    /**
     * Set reference to filesystem info
     *
     * @param FileSystemInfoInterface $fileSystemInfo
     *
     * @return $this
     */
    public function setFileSystemInfo(FileSystemInfoInterface $fileSystemInfo): self;

    /**
     * Get reference to filesystem info
     *
     * @return FileSystemInfoInterface|null
     */
    public function getFileSystemInfo(): ?FileSystemInfoInterface;
}
