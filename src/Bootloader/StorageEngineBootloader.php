<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\StorageEngine\Parser\UriParser;
use Spiral\StorageEngine\Parser\UriParserInterface;
use Spiral\StorageEngine\ResolveManager;
use Spiral\StorageEngine\ResolveManagerInterface;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\StorageInterface;

class StorageEngineBootloader extends Bootloader
{
    protected const BINDINGS = [
        StorageInterface::class => StorageEngine::class,
        ResolveManagerInterface::class => ResolveManager::class,
        UriParserInterface::class => UriParser::class,
    ];
}
