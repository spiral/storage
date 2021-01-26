<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Traits;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;

trait StorageConfigTrait
{
    protected function buildStorageConfig(?array $servers = null): StorageConfig
    {
        if (empty($servers)) {
            $servers[ServerTestInterface::SERVER_NAME] = [
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::CLASS_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => ServerTestInterface::ROOT_DIR,
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ];
        }

        return new StorageConfig(
            ['servers' => $servers]
        );
    }
}