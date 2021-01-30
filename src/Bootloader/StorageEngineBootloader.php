<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Bootloader;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\FilePathResolver;
use Spiral\StorageEngine\Resolver\FilePathResolverInterface;
use Spiral\StorageEngine\Resolver\ResolveManager;
use Spiral\StorageEngine\Resolver\ResolveManagerInterface;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Validation\FilePathValidator;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class StorageEngineBootloader extends Bootloader
{
    protected const BINDINGS = [
        ResolveManagerInterface::class => ResolveManager::class,
        FilePathResolverInterface::class => FilePathResolver::class,
        FilesystemOperator::class => [self::class, 'getMountManager'],
        FilePathValidatorInterface::class => FilePathValidator::class,
    ];

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
                AdapterFactory::build($this->config->buildServerInfo($serverLabel, true))
            );
        }

        $storageEngine->init($servers);
    }

    public function getMountManager(StorageEngine $storageEngine): ?FilesystemOperator
    {
        return $storageEngine->getMountManager();
    }
}
