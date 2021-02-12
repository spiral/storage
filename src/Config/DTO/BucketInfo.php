<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;

class BucketInfo implements BucketInfoInterface
{
    public string $name;

    public string $server;

    protected ?FileSystemInfoInterface $fileSystemInfo = null;

    protected ?string $directory = null;

    public function __construct(string $name, string $server, array $info = [])
    {
        $this->name = $name;
        $this->server = $server;

        if (array_key_exists(static::DIRECTORY_KEY, $info)) {
            $this->directory = $info[static::DIRECTORY_KEY];
        }
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getDirectory(): ?string
    {
        return $this->directory;
    }

    public function setFileSystemInfo(FileSystemInfoInterface $fileSystemInfo): BucketInfoInterface
    {
        $this->fileSystemInfo = $fileSystemInfo;

        return $this;
    }

    public function getFileSystemInfo(): ?FileSystemInfoInterface
    {
        return $this->fileSystemInfo;
    }
}
