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
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;

/**
 * tests for StorageWriterInterface methods
 */
class StorageEngineWriterTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use StorageConfigTrait;

    private const LOCAL_SERVER_NAME = 'local';

    /**
     * @throws StorageException
     */
    public function testTempFileNameThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $uri = 'local://file.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('read')
            ->with('file.txt')
            ->willThrowException(
                UnableToReadFile::fromLocation('file.txt')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to read file from location: file.txt.');

        $storage->tempFilename($uri);
    }

    /**
     * @throws StorageException
     */
    public function testWriteFile(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $fileName = 'newFile.txt';
        $fileContent = 'new File content';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('write')
            ->with($fileName, $fileContent, []);

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->assertEquals(
            'local://newFile.txt',
            $storage->write(static::LOCAL_SERVER_NAME, $fileName, $fileContent)
        );
    }

    /**
     * @throws StorageException
     */
    public function testWriteFileThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $fileName = 'newFile.txt';
        $fileContent = 'new File content';
        
        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('write')
            ->with($fileName, $fileContent, [])
            ->willThrowException(
                UnableToWriteFile::atLocation($fileName, 'test reason')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

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
        $storage = $this->buildSimpleStorageEngine();

        $fileName = 'newFile.txt';
        $fileContent = 'new File content';
        $config = ['visibility' => 'public'];

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('writeStream')
            ->with($fileName, $fileContent, $config);

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->assertEquals(
            'local://newFile.txt',
            $storage->writeStream(static::LOCAL_SERVER_NAME, $fileName, $fileContent, $config)
        );
    }

    /**
     * @throws StorageException
     */
    public function testWriteStreamThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $fileName = 'newFile.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('writeStream')
            ->willThrowException(
                UnableToWriteFile::atLocation($fileName, 'test reason')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to write file at location: newFile.txt. test reason/'
        );

        $resource = fopen('php://memory', 'r+');

        $storage->writeStream(
            static::LOCAL_SERVER_NAME,
            $fileName,
            stream_get_contents($resource)
        );

        fclose($resource);
    }

    /**
     * @throws StorageException
     */
    public function testSetVisibility(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $uri = 'local://newFile.txt';
        $newVisibility = 'private';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('setVisibility')
            ->with('newFile.txt', $newVisibility);

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->setVisibility($uri, $newVisibility);
    }

    /**
     * @throws StorageException
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

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to set visibility for file newFile.txt. test reason/'
        );

        $storage->setVisibility($uri, $newVisibility);
    }

    /**
     * @throws StorageException
     */
    public function testDeleteFile(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $uri = 'local://file.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('delete')
            ->with('file.txt');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->delete($uri);
    }

    /**
     * @throws StorageException
     */
    public function testDeleteFileThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $uri = 'local://file.txt';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('delete')
            ->with('file.txt')
            ->willThrowException(
                UnableToDeleteFile::atLocation('file.txt', 'test reason')
            );

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to delete file located at: file.txt. test reason/'
        );

        $storage->delete($uri);
    }

    /**
     * @throws StorageException
     */
    public function testMoveFileInSameSystem(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $sourceUri = 'local://file.txt';
        $destinationServer = 'local';
        $targetFilePath = 'movedFile.txt';
        $config = ['visibility' => 'private'];

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('move')
            ->with('file.txt', $targetFilePath, $config);

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->move($sourceUri, $destinationServer, $targetFilePath, $config);
    }

    /**
     * @throws StorageException
     */
    public function testMoveSameFileInSameSystem(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $sourceUri = 'local://file.txt';
        $destinationServer = 'local';

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->never())
            ->method('move');

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->move($sourceUri, $destinationServer);
    }

    /**
     * @throws StorageException
     */
    public function testMoveFileAcrossSystems(): void
    {
        $storage = $this->buildSimpleStorageEngine();

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

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);
        $storage->mountFilesystem($destinationServer, $localServer2);

        $storage->move($sourceUri, $destinationServer);
    }

    /**
     * @throws StorageException
     */
    public function testMoveFileUnknownDestinationSystem(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $sourceUri = 'local://file.txt';
        $destinationServer = 'missed';

        $localServer = $this->createMock(FilesystemOperator::class);

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(MountException::class);
        $this->expectExceptionMessage('Server missed was not identified');

        $storage->move($sourceUri, $destinationServer);
    }

    /**
     * @throws StorageException
     */
    public function testMoveFileInSameSystemThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

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

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to move file from file.txt to movedFile.txt');

        $storage->move($sourceUri, $destinationServer, $targetFilePath);
    }

    /**
     * @throws StorageException
     */
    public function testCopyFileInSameSystem(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $sourceUri = 'local://file.txt';
        $destinationServer = 'local';
        $targetFilePath = 'copiedFile.txt';
        $config = ['visibility' => 'private'];

        $localServer = $this->createMock(FilesystemOperator::class);
        $localServer->expects($this->once())
            ->method('copy')
            ->with('file.txt', $targetFilePath, $config);

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $storage->copy($sourceUri, $destinationServer, $targetFilePath, $config);
    }

    /**
     * @throws StorageException
     */
    public function testCopyFileAcrossSystems(): void
    {
        $storage = $this->buildSimpleStorageEngine();

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

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);
        $storage->mountFilesystem($destinationServer, $localServer2);

        $storage->copy($sourceUri, $destinationServer, null, $config);
    }

    /**
     * @throws StorageException
     */
    public function testCopyFileUnknownDestinationSystem(): void
    {
        $storage = $this->buildSimpleStorageEngine();

        $sourceUri = 'local://file.txt';
        $destinationServer = 'missed';

        $localServer = $this->createMock(FilesystemOperator::class);

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(MountException::class);
        $this->expectExceptionMessage('Server missed was not identified');

        $storage->copy($sourceUri, $destinationServer);
    }

    /**
     * @throws StorageException
     */
    public function testCopyFileInSameSystemThrowsException(): void
    {
        $storage = $this->buildSimpleStorageEngine();

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

        $storage->mountFilesystem(static::LOCAL_SERVER_NAME, $localServer);

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Unable to move file from file.txt to movedFile.txt');

        $storage->copy($sourceUri, $destinationServer, $targetFilePath);
    }

    /**
     * @return StorageEngine
     *
     * @throws StorageException
     */
    private function buildSimpleStorageEngine(): StorageEngine
    {
        return new StorageEngine($this->buildStorageConfig(), $this->getUriResolver());
    }
}
