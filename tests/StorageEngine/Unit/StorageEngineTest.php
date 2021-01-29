<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;

class StorageEngineTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    /**
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    public function testInit(): void
    {
        $localInfo = $this->buildLocalInfo('local');

        $engine = new StorageEngine();

        $this->assertFalse($engine->isInitiated());
        $this->assertNull($engine->getMountManager());

        $engine->init(
            [
                'local' => new Filesystem(
                    AdapterFactory::build($localInfo)
                ),
            ]
        );

        $this->assertTrue($engine->isInitiated());

        $mountManager = $engine->getMountManager();

        $this->assertInstanceOf(MountManager::class, $mountManager);
        $this->assertSame($mountManager, $engine->getMountManager());
    }
}
