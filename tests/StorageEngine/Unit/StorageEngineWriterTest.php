<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Spiral\StorageEngine\Exception\FileOperationException;
use Spiral\StorageEngine\Exception\MountException;
use Spiral\StorageEngine\Exception\StorageException;

/**
 * tests for StorageWriterInterface methods
 */
class StorageEngineWriterTest extends StorageEngineAbstractTest
{
    private const LOCAL_SERVER_NAME = 'local';

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testTempFileNameThrowsException(): void
    {
        $uri = 'local://file.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('read')
            ->with('file.txt')
            ->willThrowException(
                UnableToReadFile::fromLocation('file.txt')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to read file from location: file.txt.');

        $storage->tempFilename($uri);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testWriteFile(): void
    {
        $fileName = 'newFile.txt';
        $fileContent = 'new File content';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('write')
            ->with($fileName, $fileContent, []);

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->assertEquals(
            'local://newFile.txt',
            $storage->write(static::LOCAL_SERVER_NAME, $fileName, $fileContent)
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testWriteFileThrowsException(): void
    {
        $fileName = 'newFile.txt';
        $fileContent = 'new File content';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('write')
            ->with($fileName, $fileContent, [])
            ->willThrowException(
                UnableToWriteFile::atLocation($fileName, 'test reason')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to write file at location: newFile.txt. test reason/'
        );

        $storage->write(static::LOCAL_SERVER_NAME, $fileName, $fileContent);
    }

    /**
     * @throws StorageException
     */
    public function testWriteStream(): void
    {
        $fileName = 'newFile.txt';
        $fileContent = 'new File content';
        $config = ['visibility' => 'public'];

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('writeStream')
            ->with($fileName, $fileContent, $config);

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->assertEquals(
            'local://newFile.txt',
            $storage->writeStream(static::LOCAL_SERVER_NAME, $fileName, $fileContent, $config)
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testWriteStreamThrowsException(): void
    {
        $fileName = 'newFile.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('writeStream')
            ->willThrowException(
                UnableToWriteFile::atLocation($fileName, 'test reason')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to write file at location: newFile.txt. test reason/'
        );

        $resource = fopen('php://memory', 'rb+');

        $storage->writeStream(
            static::LOCAL_SERVER_NAME,
            $fileName,
            stream_get_contents($resource)
        );

        fclose($resource);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testSetVisibility(): void
    {
        $uri = 'local://newFile.txt';
        $newVisibility = 'private';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('setVisibility')
            ->with('newFile.txt', $newVisibility);

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->setVisibility($uri, $newVisibility);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testSetVisibilityThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $uri = 'local://newFile.txt';
        $newVisibility = 'private';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('setVisibility')
            ->with('newFile.txt', $newVisibility)
            ->willThrowException(
                UnableToSetVisibility::atLocation('newFile.txt', 'test reason')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to set visibility for file newFile.txt. test reason/'
        );

        $storage->setVisibility($uri, $newVisibility);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testDeleteFile(): void
    {
        $uri = 'local://file.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('delete')
            ->with('file.txt');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->delete($uri);
    }

    /**
     * @throws StorageException
     */
    public function testDeleteFileThrowsException(): void
    {
        $uri = 'local://file.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('delete')
            ->with('file.txt')
            ->willThrowException(
                UnableToDeleteFile::atLocation('file.txt', 'test reason')
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to delete file located at: file.txt. test reason/'
        );

        $storage->delete($uri);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMoveFileInSameSystem(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'local';
        $targetFilePath = 'movedFile.txt';
        $config = ['visibility' => 'private'];

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('move')
            ->with('file.txt', $targetFilePath, $config);

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->move($sourceUri, $destinationServer, $targetFilePath, $config);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMoveSameFileInSameSystem(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'local';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->never())
            ->method('move');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->move($sourceUri, $destinationServer);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMoveFileAcrossSystems(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'local2';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('readStream')
            ->with('file.txt');
        $localServer->expects($this->once())
            ->method('delete')
            ->with('file.txt');


        $localServer2 = $this->createMock(FilesystemOperator::class);
        $localServer2->expects($this->once())
            ->method('writeStream');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);
        $this->mountStorageEngineFileSystem($storage, $destinationServer, $localServer2);

        $storage->move($sourceUri, $destinationServer);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMoveFileUnknownDestinationSystem(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'missed';

        $localServer = $this->createMock(FilesystemOperator::class);

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(MountException::class);
        $this->expectExceptionMessage('Server missed was not identified');

        $storage->move($sourceUri, $destinationServer);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMoveFileInSameSystemThrowsException(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'local';
        $targetFilePath = 'movedFile.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('move')
            ->with('file.txt', $targetFilePath, [])
            ->willThrowException(
                UnableToMoveFile::fromLocationTo('file.txt', $targetFilePath)
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to move file from file.txt to movedFile.txt');

        $storage->move($sourceUri, $destinationServer, $targetFilePath);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testCopyFileInSameSystem(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'local';
        $targetFilePath = 'copiedFile.txt';
        $config = ['visibility' => 'private'];

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('copy')
            ->with('file.txt', $targetFilePath, $config);

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $storage->copy($sourceUri, $destinationServer, $targetFilePath, $config);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testCopyFileAcrossSystems(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'local2';
        $config = ['visibility' => 'public'];

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('readStream')
            ->with('file.txt');

        $localServer2 = $this->createMock(FilesystemOperator::class);
        $localServer2->expects($this->once())
            ->method('writeStream');

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);
        $this->mountStorageEngineFileSystem($storage, $destinationServer, $localServer2);

        $storage->copy($sourceUri, $destinationServer, null, $config);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testCopyFileUnknownDestinationSystem(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'missed';

        $localServer = $this->createMock(FilesystemOperator::class);

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(MountException::class);
        $this->expectExceptionMessage('Server missed was not identified');

        $storage->copy($sourceUri, $destinationServer);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testCopyFileInSameSystemThrowsException(): void
    {
        $sourceUri = 'local://file.txt';
        $destinationServer = 'local';
        $targetFilePath = 'movedFile.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('copy')
            ->with('file.txt', $targetFilePath, [])
            ->willThrowException(
                UnableToCopyFile::fromLocationTo('file.txt', $targetFilePath)
            );

        $storage = $this->buildSimpleStorageEngine(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to move file from file.txt to movedFile.txt');

        $storage->copy($sourceUri, $destinationServer, $targetFilePath);
    }
}
