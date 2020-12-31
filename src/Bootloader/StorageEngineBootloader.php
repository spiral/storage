<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\StorageEngine\Config\StorageConfig;

class StorageEngineBootloader extends Bootloader
{
    private StorageConfig $config;

    public function __construct(StorageConfig $config)
    {
        $this->config = $config;
    }

    public function boot(): void
    {
        foreach ($this->config->getServersKeys() as $serverLabel) {
            $serverInfo = $this->config->buildServerInfo($serverLabel);
        }
    }
}