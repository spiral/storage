<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\MountManager;
use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Resolver\ResolveManagerInterface;

class StorageEngine implements SingletonInterface
{
    private ?MountManager $mountManager = null;

    private ResolveManagerInterface $resolveManager;

    public function __construct(ResolveManagerInterface $resolveManager)
    {
        $this->resolveManager = $resolveManager;
    }

    /**
     * @param array<string, FilesystemOperator> $servers
     *
     * @throws Exception\StorageException
     */
    public function init(array $servers): void
    {
        $this->mountManager = new MountManager($servers);
        $this->resolveManager->initResolvers();
    }

    public function isInitiated(): bool
    {
        return $this->mountManager instanceof MountManager;
    }

    public function getMountManager(): ?MountManager
    {
        return $this->mountManager;
    }

    public function getResolveManager(): ResolveManagerInterface
    {
        return $this->resolveManager;
    }
}
