<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;

class StorageEngineTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    /**
     * @throws StorageException
     */
    public function testInit(): void
    {
        $localInfo = $this->buildLocalInfo('local');

        $engine = new StorageEngine($this->getUriResolver());

        $this->assertFalse($engine->isInitiated(false));
        $this->assertNull($engine->getMountManager());

        $engine->init(
            [
                'local' => new Filesystem(
                    AdapterFactory::build($localInfo)
                ),
            ]
        );

        $this->assertTrue($engine->isInitiated(false));

        $mountManager = $engine->getMountManager();

        $this->assertInstanceOf(MountManager::class, $mountManager);
        $this->assertSame($mountManager, $engine->getMountManager());
    }

    /**
     * @throws StorageException
     */
    public function testIsInitiatedThrowsException(): void
    {
        $engine = new StorageEngine($this->getUriResolver());

        $this->expectExceptionMessage(StorageException::class);
        $this->expectExceptionMessage('Storage engine was not initiated!');

        $engine->isInitiated();
    }
}
