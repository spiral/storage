<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Bootloader;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\UriResolver;
use Spiral\StorageEngine\Resolver\UriResolverInterface;
use Spiral\StorageEngine\ResolveManager;
use Spiral\StorageEngine\ResolveManagerInterface;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\StorageInterface;
use Spiral\StorageEngine\Validation\FilePathValidator;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class StorageEngineBootloader extends Bootloader
{
    protected const BINDINGS = [
        StorageInterface::class => StorageEngine::class,
        ResolveManagerInterface::class => ResolveManager::class,
        UriResolverInterface::class => UriResolver::class,
        FilesystemOperator::class => [self::class, 'getMountManager'],
        FilePathValidatorInterface::class => FilePathValidator::class,
    ];

    private StorageConfig $config;

    public function __construct(StorageConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param StorageInterface $storageEngine
     *
     * @throws StorageException
     */
    public function boot(StorageInterface $storageEngine): void
    {
        $servers = [];

        foreach ($this->config->getServersKeys() as $serverLabel) {
            $servers[$serverLabel] = new Filesystem(
                AdapterFactory::build($this->config->buildServerInfo($serverLabel, true))
            );
        }

        $storageEngine->init($servers);
    }

    public function getMountManager(StorageInterface $storageEngine): ?FilesystemOperator
    {
        return $storageEngine->getMountManager();
    }
}
