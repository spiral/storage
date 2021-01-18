<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Resolver\ResolveManager;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\ReflectionHelperTrait;

class StorageEngineTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use ReflectionHelperTrait;

    public function testIsInitiated(): void
    {
        $engine = $this->buildStorageEngine();

        $this->assertFalse($engine->isInitiated());
    }

    /**
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    public function testInit(): void
    {
        $localInfo = $this->buildLocalInfo('local');

        $engine = $this->buildStorageEngine(
            ['local' => $this->buildLocalInfoDescription()]
        );

        $resolveManager = $engine->getResolveManager();
        $this->assertInstanceOf(ResolveManager::class, $resolveManager);
        $this->assertSame($resolveManager, $engine->getResolveManager());
        $this->assertEmpty($this->getProtectedProperty($resolveManager, 'resolvers'));

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

        $resolveManager = $engine->getResolveManager();
        $this->assertInstanceOf(ResolveManager::class, $resolveManager);
        $this->assertSame($resolveManager, $engine->getResolveManager());
        $this->assertNotEmpty($this->getProtectedProperty($resolveManager, 'resolvers'));
    }

    protected function buildStorageEngine(array $servers = []): StorageEngine
    {
        return new StorageEngine(
            new ResolveManager(
                new StorageConfig(
                    ['servers' => $servers]
                )
            )
        );
    }
}
