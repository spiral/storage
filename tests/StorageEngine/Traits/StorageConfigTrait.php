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
    protected function buildConfig(
        $serverName = ServerTestInterface::SERVER_NAME,
        ?array $serverInfo = null
    ): StorageConfig {
        if ($serverInfo === null) {
            $serverInfo = [
                'driver' => AdapterName::LOCAL,
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    LocalInfo::ROOT_DIR_OPTION => ServerTestInterface::ROOT_DIR,
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ];
        }

        return new StorageConfig(
            [
                'servers' => [$serverName => $serverInfo],
            ]
        );
    }
}
