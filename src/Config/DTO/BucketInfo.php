<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\StorageEngine\Config\DTO\Traits\OptionsTrait;

class BucketInfo implements BucketInfoInterface
{
    use OptionsTrait;

    public string $name;

    public string $server;

    protected ?FileSystemInfoInterface $fileSystemInfo = null;

    public function __construct(string $name, string $server, array $info = [])
    {
        $this->name = $name;
        $this->server = $server;

        if (array_key_exists(static::OPTIONS_KEY, $info)) {
            $this->options = $info[static::OPTIONS_KEY];
        }
    }

    public function getServer(): string
    {
        return $this->server;
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
