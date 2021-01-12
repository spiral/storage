<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\MountManager;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\StorageEngine;
use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\ReflectionHelperTrait;

class StorageEngineTest extends TestCase
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

    public function testGetResolver(): void
    {
        $localInfo = $this->buildLocalInfo('local');

        $engine = $this->buildStorageEngine(
            ['local' => $this->buildLocalInfoDescription()]
        );

        $engine->init(['local' => new Filesystem(AdapterFactory::build($localInfo))]);

        $resolver = $engine->getResolver('local');
        $this->assertInstanceOf(LocalSystemResolver::class, $resolver);
        $this->assertSame($resolver, $engine->getResolver('local'));
    }

    public function testGetResolverFailed(): void
    {
        $localInfo = $this->buildLocalInfo('local');

        $engine = $this->buildStorageEngine(
            ['local' => $this->buildLocalInfoDescription()]
        );

        $engine->init(['local' => new Filesystem(AdapterFactory::build($localInfo))]);


        $missedServer = 'missedServer';

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(\sprintf('No resolver was detected for server %s', $missedServer));

        $engine->getResolver($missedServer);
    }

    /**
     * @throws \ReflectionException
     */
    public function testPrepareResolverByDriver(): void
    {
        $engine = $this->buildStorageEngine();

        $resolver = $this->callNotPublicMethod($engine, 'prepareResolverByDriver', [AdapterName::LOCAL]);

        $this->assertInstanceOf(LocalSystemResolver::class, $resolver);
    }

    /**
     * @throws \ReflectionException
     */
    public function testPrepareResolverByUnknownDriver(): void
    {
        $driver = 'missedDriver';
        $engine = $this->buildStorageEngine();

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(\sprintf('No resolver was detected for driver %s', $driver));

        $this->callNotPublicMethod($engine, 'prepareResolverByDriver', [$driver]);
    }

    private function buildStorageEngine(array $servers = []): StorageEngine
    {
        return new StorageEngine(
            new StorageConfig(
                ['servers' => $servers]
            )
        );
    }
}
