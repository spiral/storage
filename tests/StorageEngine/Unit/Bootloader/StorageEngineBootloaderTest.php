<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Bootloader;

use League\Flysystem\MountManager;
use Spiral\StorageEngine\Bootloader\StorageEngineBootloader;
use Spiral\StorageEngine\Config\StorageConfig;
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

        $engine = new StorageEngine();

        $this->assertFalse($engine->isInitiated());

        $bootloader = new StorageEngineBootloader($config);

        $bootloader->boot($engine);

        $this->assertTrue($engine->isInitiated());
    }

    /**
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    public function testGetMountManager(): void
    {
        $config = new StorageConfig(
            [
                'servers' => [
                    'local' => $this->buildLocalInfoDescription(),
                ],
            ]
        );

        $engine = new StorageEngine();

        $bootloader = new StorageEngineBootloader($config);

        $this->assertNull($bootloader->getMountManager($engine));

        $bootloader->boot($engine);

        $this->assertInstanceOf(MountManager::class, $bootloader->getMountManager($engine));

        $this->assertTrue($engine->isInitiated());
    }
}
