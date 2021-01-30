<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config;

use Spiral\Core\Exception\ConfigException;
use Spiral\Core\InjectableConfig;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\StorageException;

class StorageConfig extends InjectableConfig
{
    public const CONFIG = 'storage';

    private const SERVERS_KEY = 'servers';

    protected $config = [
        self::SERVERS_KEY   => [],
    ];

    /**
     * @var ServerInfoInterface[]
     *
     * Internal list allows to keep once built server info
     */
    protected array $serversInfo = [];

    public function getServersKeys(): array
    {
        return array_keys($this->config[static::SERVERS_KEY]);
    }

    public function hasServer(string $key): bool
    {
        return array_key_exists($key, $this->config[static::SERVERS_KEY]);
    }

    /**
     * Build server info by provided label
     * Force mode allows to rebuild server info for internal servers info list
     *
     * @param string $serverLabel
     * @param bool|null $force
     *
     * @return ServerInfoInterface
     *
     * @throws StorageException
     */
    public function buildServerInfo(string $serverLabel, ?bool $force = false): ServerInfoInterface
    {
        if (!$this->hasServer($serverLabel)) {
            throw new ConfigException(
                \sprintf(
                    'Server %s was not found',
                    $serverLabel
                )
            );
        }

        if (!$force && array_key_exists($serverLabel, $this->serversInfo)) {
            return $this->serversInfo[$serverLabel];
        }

        $serverInfo = $this->config[static::SERVERS_KEY][$serverLabel];

        switch ($this->extractServerAdapter($serverInfo)) {
            case \League\Flysystem\Local\LocalFilesystemAdapter::class:
                $serverInfoDTO = new LocalInfo($serverLabel, $serverInfo);
                break;
            case \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class:
            case \League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter::class:
                $serverInfoDTO = new AwsS3Info($serverLabel, $serverInfo);
                break;
            default:
                throw new ConfigException('Adapter can\'t be identified for server ' . $serverLabel);
        }

        $this->serversInfo[$serverLabel] = $serverInfoDTO;

        return $serverInfoDTO;
    }

    private function extractServerAdapter(array $serverInfo): ?string
    {
        return array_key_exists(ServerInfoInterface::ADAPTER_KEY, $serverInfo)
            ? $serverInfo[ServerInfoInterface::ADAPTER_KEY]
            : null;
    }
}
