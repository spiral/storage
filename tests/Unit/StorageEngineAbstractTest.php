<?php

declare(strict_types=1);

namespace Spiral\Tests\Storage\Unit;

use League\Flysystem\FilesystemOperator;
use Spiral\Storage\Exception\ConfigException;
use Spiral\Storage\Exception\StorageException;
use Spiral\Storage\StorageEngine;
use Spiral\Tests\Storage\Traits\LocalFsBuilderTrait;

abstract class StorageEngineAbstractTest extends UnitTestCase
{
    use LocalFsBuilderTrait;

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
