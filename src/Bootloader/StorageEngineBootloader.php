<?php

declare(strict_types=1);

namespace Spiral\Storage\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Storage\Parser\UriParser;
use Spiral\Storage\Parser\UriParserInterface;
use Spiral\Storage\ResolveManager;
use Spiral\Storage\ResolveManagerInterface;
use Spiral\Storage\StorageEngine;
use Spiral\Storage\StorageInterface;

class StorageEngineBootloader extends Bootloader
{
    protected const BINDINGS = [
        StorageInterface::class => StorageEngine::class,
        ResolveManagerInterface::class => ResolveManager::class,
        UriParserInterface::class => UriParser::class,
    ];
}
