<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\FilesystemOperator;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalFsBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;

abstract class StorageEngineAbstractTest extends AbstractUnitTest
{
    use LocalFsBuilderTrait;
    use StorageConfigTrait;

    /**
     * @param string|null $fs
     * @param FilesystemOperator|null $fileSystem
     *
     * @return StorageEngine
     *
     * @throws StorageException
     * @throws ConfigException
     * @throws \ReflectionException
     */
    protected function buildSimpleStorageEngine(
        ?string $fs = null,
        ?FilesystemOperator $fileSystem = null
    ): StorageEngine {
        $engine = new StorageEngine($this->buildStorageConfig(), $this->getUriParser());

        if (!empty($fs) && $fileSystem !== null) {
            $this->mountStorageEngineFileSystem($engine, $fs, $fileSystem);
        }

        return $engine;
    }

    /**
     * @param StorageEngine $engine
     * @param string $fs
     * @param FilesystemOperator $fileSystem
     *
     * @throws \ReflectionException
     */
    protected function mountStorageEngineFileSystem(
        StorageEngine $engine,
        string $fs,
        FilesystemOperator $fileSystem
    ): void {
        $this->callNotPublicMethod($engine, 'mountFilesystem', [$fs, $fileSystem]);
    }
}
