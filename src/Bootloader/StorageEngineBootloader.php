<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Core\Container;
use Spiral\Storage\Config\StorageConfig;
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

    public function boot(Container $app): void
    {
        $app->bindInjector(StorageConfig::class, ConfiguratorInterface::class);
    }
}
