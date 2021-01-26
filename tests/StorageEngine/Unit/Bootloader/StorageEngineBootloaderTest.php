<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Bootloader;

use Spiral\StorageEngine\Bootloader\StorageEngineBootloader;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Resolver\ResolveManager;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class StorageEngineBootloaderTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    /**
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    public function testInit(): void
    {
        $config = new StorageConfig(
            [
                'servers' => [
                    'local' => $this->buildLocalInfoDescription(),
                ],
            ]
        );

        $engine = new StorageEngine(
            new ResolveManager($config)
        );

        $this->assertFalse($engine->isInitiated());

        $bootloader = new StorageEngineBootloader($config);

        $bootloader->boot($engine);


        $this->assertTrue($engine->isInitiated());
    }
}