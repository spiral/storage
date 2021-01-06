<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\MountManager;
use Spiral\Core\Container\SingletonInterface;

class StorageEngine implements SingletonInterface
{
    private ?MountManager $manager = null;

    /**
     * @param array<string, FilesystemOperator> $servers
     */
    public function init(array $servers): void
    {
        $this->manager = new MountManager($servers);
    }

    public function isInitiated(): bool
    {
        return $this->manager instanceof MountManager;
    }
}
