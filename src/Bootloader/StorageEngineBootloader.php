<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
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
        FilePathValidatorInterface::class => FilePathValidator::class,
    ];
}
