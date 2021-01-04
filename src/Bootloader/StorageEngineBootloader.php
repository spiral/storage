<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Bootloader;

use League\Flysystem\Filesystem;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;

class StorageEngineBootloader extends Bootloader
{
    private StorageConfig $config;

    public function __construct(StorageConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param StorageEngine $storageEngine
     *
     * @throws StorageException
     */
    public function boot(StorageEngine $storageEngine): void
    {
        $servers = [];

        foreach ($this->config->getServersKeys() as $serverLabel) {
            $servers[$serverLabel] = new Filesystem(
                AdapterFactory::build($this->config->buildServerInfo($serverLabel))
            );
        }

        $storageEngine->init($servers);
    }
}
