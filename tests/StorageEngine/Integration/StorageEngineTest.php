<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Integration;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\AbstractTest;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;

class StorageEngineTest extends AbstractTest
{
    use LocalServerBuilderTrait;

    private vfsStreamDirectory $rootDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = vfsStream::setup(ServerTestInterface::ROOT_DIR_NAME, 777);
    }

    /**
     * @throws StorageException
     */
    public function testLocalServerFileExists(): void
    {
        $file = 'file.txt';
        $fileContent = 'file text';

        vfsStream::create(
            [
                $file => $fileContent,
            ],
            $this->rootDir
        );

        $this->assertTrue(
            $this->buildStorageForLocal()->fileExists('local://file.txt')
        );

        $this->assertFalse(
            $this->buildStorageForLocal()->fileExists('local://file2.txt')
        );
    }

    /**
     * @throws StorageException
     */
    public function testWrongLocalServerFileExistsThrowsException(): void
    {
        $file = 'file.txt';
        $fileContent = 'file text';

        vfsStream::create(
            [
                $file => $fileContent,
            ],
            $this->rootDir
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            'Unable to resolve the filesystem mount because the mount (other) was not registered.'
        );

        $this->buildStorageForLocal()->fileExists('other://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testWrongFormatServerFileExistsThrowsException(): void
    {
        $file = 'file.txt';
        $fileContent = 'file text';

        vfsStream::create(
            [
                $file => $fileContent,
            ],
            $this->rootDir
        );

        $uri = 'other-//file.txt';

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('File %s can\'t be identified', $uri)
        );

        $this->buildStorageForLocal()->fileExists($uri);
    }

    /**
     * @return StorageEngine
     *
     * @throws StorageException
     */
    private function buildStorageForLocal(): StorageEngine
    {
        $storageEngine = new StorageEngine($this->getUriResolver());
        $storageEngine->init(
            ['local' => $this->buildLocalServer(true)]
        );

        return $storageEngine;
    }
}
