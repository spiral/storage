<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use Spiral\StorageEngine\Exception\FileOperationException;
use Spiral\StorageEngine\Exception\StorageException;

/**
 * tests for StorageReaderInterface methods
 */
class StorageEngineReaderTest extends StorageEngineAbstractTest
{
    private const LOCAL_SERVER_NAME = 'local';

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testFileExists(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('fileExists')
            ->with('file.txt');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->fileExists('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testFileExistsThrowsException(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('fileExists')
            ->willThrowException(
                UnableToCheckFileExistence::forLocation('file.txt')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to check file existence for: file.txt');

        $storage->fileExists('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testRead(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('read')
            ->with('file.txt');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->read('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testReadThrowsException(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('read')
            ->willThrowException(
                UnableToReadFile::fromLocation('file.txt')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to read file from location: file.txt.');

        $storage->read('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testReadStream(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('readStream')
            ->with('file.txt');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->readStream('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testReadStreamThrowsException(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('readStream')
            ->willThrowException(
                UnableToReadFile::fromLocation('file.txt')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to read file from location: file.txt.');

        $storage->readStream('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testLastModified(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('lastModified')
            ->with('file.txt');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->lastModified('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testLastModifiedThrowsException(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('lastModified')
            ->willThrowException(
                UnableToRetrieveMetadata::lastModified('file.txt')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to retrieve the last_modified for file at location: file.txt.');

        $storage->lastModified('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testFileSize(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('fileSize')
            ->with('file.txt');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->fileSize('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testFileSizeThrowsException(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('fileSize')
            ->willThrowException(
                UnableToRetrieveMetadata::fileSize('file.txt')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to retrieve the file_size for file at location: file.txt.');

        $storage->fileSize('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMimeType(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('mimeType')
            ->with('file.txt');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->mimeType('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMimeTypeThrowsException(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('mimeType')
            ->willThrowException(
                UnableToRetrieveMetadata::mimeType('file.txt')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to retrieve the mime_type for file at location: file.txt.');

        $storage->mimeType('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testVisibility(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('visibility')
            ->with('file.txt');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->visibility('local://file.txt');
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testVisibilityThrowsException(): void
    {
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('visibility')
            ->willThrowException(
                UnableToRetrieveMetadata::visibility('file.txt')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to retrieve the visibility for file at location: file.txt.');

        $storage->visibility('local://file.txt');
    }
}
