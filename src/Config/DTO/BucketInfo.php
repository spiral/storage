<?php

declare(strict_types=1);

namespace Spiral\Storage\Config\DTO;

use Spiral\Storage\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\Storage\Config\DTO\Traits\OptionsTrait;

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

    /**
     * @inheritDoc
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @inheritDoc
     */
    public function setFileSystemInfo(FileSystemInfoInterface $fileSystemInfo): BucketInfoInterface
    {
        $this->fileSystemInfo = $fileSystemInfo;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFileSystemInfo(): ?FileSystemInfoInterface
    {
        return $this->fileSystemInfo;
    }
}
