<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;

/**
 * tests for StorageReaderInterface methods
 */
/*class StorageEngineReaderTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use StorageConfigTrait;

    private StorageEngine $storage;

    private FilesystemOperator $localFileSystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localFileSystem = new Filesystem(
            AdapterFactory::build(
                $this->buildLocalInfo(ServerTestInterface::SERVER_NAME, false)
            )
        );

        $storageConfig = $this->buildStorageConfig(
             ['default' => $this->buildLocalInfoDescription()]
        );

        $this->storage = new StorageEngine($storageConfig, $this->getUriResolver());
        $this->storage->mountFilesystem(ServerTestInterface::SERVER_NAME, $this->localFileSystem);
    }



    private function buildSimpleStorageEngine(): StorageEngine
    {
        return new StorageEngine($this->buildStorageConfig(), $this->getUriResolver());
    }
}
*/
