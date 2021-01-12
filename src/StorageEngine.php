<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\MountManager;
use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\Resolver\ResolverInterface;

class StorageEngine implements SingletonInterface
{
    private StorageConfig $config;

    private ?MountManager $mountManager = null;

    /**
     * @var ResolverInterface[]
     */
    private array $resolvers = [];

    public function __construct(StorageConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string, FilesystemOperator> $servers
     *
     * @throws Exception\StorageException
     */
    public function init(array $servers): void
    {
        $this->mountManager = new MountManager($servers);
        $this->initResolvers();
    }

    public function isInitiated(): bool
    {
        return $this->mountManager instanceof MountManager;
    }

    public function getMountManager(): MountManager
    {
        return $this->mountManager;
    }

    /**
     * @param string $serverKey
     *
     * @return ResolverInterface
     *
     * @throws StorageException
     */
    public function getResolver(string $serverKey): ResolverInterface
    {
        if (!array_key_exists($serverKey, $this->resolvers)) {
            throw new StorageException('No resolver was detected for server ' . $serverKey);
        }

        return $this->resolvers[$serverKey];
    }

    /**
     * @throws Exception\StorageException
     */
    private function initResolvers(): void
    {
        foreach ($this->config->getServersKeys() as $serverKey) {
            $this->resolvers[$serverKey] = $this->prepareResolverByDriver(
                $this->config->buildServerInfo($serverKey)->getDriver()
            );
        }
    }

    /**
     * @param string $driver
     *
     * @return ResolverInterface
     *
     * @throws StorageException
     */
    private function prepareResolverByDriver(string $driver): ResolverInterface
    {
        switch ($driver) {
            case AdapterName::LOCAL:
                return new LocalSystemResolver($this->config);
            default:
                throw new StorageException('No resolver was detected for driver ' . $driver);
        }
    }
}
