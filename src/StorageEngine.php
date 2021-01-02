<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use Doctrine\Common\Collections\ArrayCollection;
use League\Flysystem\Filesystem;

class StorageEngine
{
    private ArrayCollection $servers;

    public function __construct()
    {
        $this->servers = new ArrayCollection();
    }

    public function hasServer(string $server): bool
    {
        return $this->servers->containsKey($server);
    }

    public function addServer(string $server, Filesystem $filesystem): void
    {
        $this->servers->set($server, $filesystem);
    }

    public function getServer(string $server): ?Filesystem
    {
        return $this->hasServer($server) ? $this->servers->get($server) : null;
    }
}
