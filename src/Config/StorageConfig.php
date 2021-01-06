<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config;

use Spiral\Core\Exception\ConfigException;
use Spiral\Core\InjectableConfig;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Enum\HttpStatusCode;
use Spiral\StorageEngine\Exception\StorageException;

class StorageConfig extends InjectableConfig
{
    public const CONFIG = 'storage';

    private const SERVERS_KEY = 'servers';
    private const DRIVER_KEY = 'driver';

    protected $config = [
        self::SERVERS_KEY   => [],
    ];

    /**
     * @var ServerInfoInterface[]
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
                ),
                HttpStatusCode::NOT_FOUND
            );
        }

        if (!$force && array_key_exists($serverLabel, $this->serversInfo)) {
            return $this->serversInfo[$serverLabel];
        }

        $serverInfo = $this->config[static::SERVERS_KEY][$serverLabel];

        if (
            !array_key_exists(static::DRIVER_KEY, $serverInfo)
            || !in_array($serverInfo[static::DRIVER_KEY], AdapterName::ALL, true)
        ) {
            throw new ConfigException(
                \sprintf(
                    'Server driver for %s was not identified',
                    $serverLabel
                ),
                HttpStatusCode::ERROR
            );
        }

        switch ($serverInfo[static::DRIVER_KEY]) {
            case AdapterName::LOCAL:
                $serverInfoDTO = new LocalInfo($serverLabel, $serverInfo);
                break;
            default:
                throw new ConfigException('Driver info can\'t be built for driver ' . $serverLabel);
        }

        $this->serversInfo[$serverLabel] = $serverInfoDTO;

        return $serverInfoDTO;
    }
}
