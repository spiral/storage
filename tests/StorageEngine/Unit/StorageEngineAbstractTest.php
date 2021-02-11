<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\FilesystemOperator;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;

abstract class StorageEngineAbstractTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use StorageConfigTrait;

    /**
     * @param string|null $mountServerKey
     * @param FilesystemOperator|null $fileSystem
     *
     * @return StorageEngine
     *
     * @throws StorageException
     * @throws ConfigException
     * @throws \ReflectionException
     */
    protected function buildSimpleStorageEngine(
        ?string $mountServerKey = null,
        ?FilesystemOperator $fileSystem = null
    ): StorageEngine {
        $engine = new StorageEngine($this->buildStorageConfig(), $this->getUriParser());

        if (!empty($mountServerKey) && $fileSystem !== null) {
            $this->mountStorageEngineFileSystem($engine, $mountServerKey, $fileSystem);
        }

        return $engine;
    }

    /**
     * @param StorageEngine $engine
     * @param string $serverKey
     * @param FilesystemOperator $fileSystem
     *
     * @throws \ReflectionException
     */
    protected function mountStorageEngineFileSystem(
        StorageEngine $engine,
        string $serverKey,
        FilesystemOperator $fileSystem
    ): void {
        $this->callNotPublicMethod($engine, 'mountFilesystem', [$serverKey, $fileSystem]);
    }
}
