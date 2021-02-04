<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use Spiral\StorageEngine\Exception\FileOperationException;
use Spiral\StorageEngine\Exception\MountException;
use Spiral\StorageEngine\Exception\StorageException;

/**
 * tests for StorageReaderInterface methods
 */
class StorageEngineReaderTest extends StorageEngineAbstractTest
{
    private const LOCAL_SERVER_NAME = 'local';

    /**
     * @throws StorageException
     */
    public function testFileExists(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('fileExists')
            ->with('file.txt');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->fileExists('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testFileExistsThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('fileExists')
            ->willThrowException(
                UnableToCheckFileExistence::forLocation('file.txt')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to check file existence for: file.txt');

        $storage->fileExists('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testRead(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('read')
            ->with('file.txt');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->read('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testReadThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('read')
            ->willThrowException(
                UnableToReadFile::fromLocation('file.txt')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to read file from location: file.txt.');

        $storage->read('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testReadStream(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('readStream')
            ->with('file.txt');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->readStream('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testReadStreamThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('readStream')
            ->willThrowException(
                UnableToReadFile::fromLocation('file.txt')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to read file from location: file.txt.');

        $storage->readStream('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testLastModified(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('lastModified')
            ->with('file.txt');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->lastModified('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testLastModifiedThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('lastModified')
            ->willThrowException(
                UnableToRetrieveMetadata::lastModified('file.txt')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to retrieve the last_modified for file at location: file.txt.');

        $storage->lastModified('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testFileSize(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('fileSize')
            ->with('file.txt');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->fileSize('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testFileSizeThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('fileSize')
            ->willThrowException(
                UnableToRetrieveMetadata::fileSize('file.txt')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to retrieve the file_size for file at location: file.txt.');

        $storage->fileSize('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testMimeType(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('mimeType')
            ->with('file.txt');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->mimeType('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testMimeTypeThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('mimeType')
            ->willThrowException(
                UnableToRetrieveMetadata::mimeType('file.txt')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to retrieve the mime_type for file at location: file.txt.');

        $storage->mimeType('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testVisibility(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('visibility')
            ->with('file.txt');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->visibility('local://file.txt');
    }

    /**
     * @throws StorageException
     */
    public function testVisibilityThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('visibility')
            ->willThrowException(
                UnableToRetrieveMetadata::visibility('file.txt')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to retrieve the visibility for file at location: file.txt.');

        $storage->visibility('local://file.txt');
    }

    /*public function testFileSize(): void {
        
    }

    public function testMimeType(): void {
        
    }

    public function testVisibility(): void {
        
    }*/
}

