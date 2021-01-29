<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\MountManager;
use Spiral\Core\Container\SingletonInterface;

class StorageEngine implements SingletonInterface
{
    private ?MountManager $mountManager = null;

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
