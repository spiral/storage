<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\MountManager;
use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Resolver\FilePathResolverInterface;

class StorageEngine implements StorageInterface, SingletonInterface
{
    protected ?MountManager $mountManager = null;

    private FilePathResolverInterface $filePathResolver;

    public function __construct(FilePathResolverInterface $filePathResolver)
    {
        $this->filePathResolver = $filePathResolver;
    }

    /**
     * @param array<string, FilesystemOperator> $servers
     */
    public function init(array $servers): void
    {
        $this->mountManager = new MountManager($servers);
    }

    public function isInitiated(): bool
    {
        return $this->mountManager instanceof MountManager;
    }

    public function getMountManager(): ?MountManager
    {
        return $this->mountManager;
    }
}
