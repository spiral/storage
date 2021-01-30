<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Traits;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;

trait StorageConfigTrait
{
    protected function buildStorageConfig(?array $servers = null): StorageConfig
    {
        if (empty($servers)) {
            $servers[ServerTestInterface::SERVER_NAME] = [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => ServerTestInterface::ROOT_DIR,
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
                ],
            ];
        }

        return new StorageConfig(
            ['servers' => $servers]
        );
    }
}
