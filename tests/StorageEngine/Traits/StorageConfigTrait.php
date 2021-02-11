<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Traits;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;

trait StorageConfigTrait
{
    /**
     * @param array|null $servers
     * @param array|null $buckets
     *
     * @return StorageConfig
     *
     * @throws ConfigException
     */
    protected function buildStorageConfig(?array $servers = null, ?array $buckets = null): StorageConfig
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

        if (!empty($servers) && empty($buckets)) {
            $buckets = [];
            foreach ($servers as $serverKey => $serverInfo) {
                $buckets[$serverKey . 'B'] = $this->buildServerBucketInfoDesc($serverKey);
            }
        }

        return new StorageConfig(
            [
                'servers' => $servers,
                'buckets' => $buckets,
            ]
        );
    }

    protected function buildServerBucketInfoDesc(string $serverName): array
    {
        return [
            'server' => $serverName,
            'directory' => 'tmp/',
        ];
    }
}
