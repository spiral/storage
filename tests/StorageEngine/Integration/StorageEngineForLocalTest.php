<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Integration;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\FileOperationException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\AbstractTest;
use Spiral\StorageEngine\Tests\Interfaces\FsTestInterface;
use Spiral\StorageEngine\Tests\Traits\LocalFsBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;

class StorageEngineForLocalTest extends AbstractTest
{
    use LocalFsBuilderTrait;
    use StorageConfigTrait;

    private const ROOT_FILE_NAME = 'file.txt';
    private const ROOT_FILE_CONTENT = 'file text';

    private const ROOT_DIR_NAME = 'someDir';

    private vfsStreamDirectory $rootDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = vfsStream::setup(FsTestInterface::ROOT_DIR_NAME, 777);
    }

    /**
     * @throws StorageException
     */
    public function testTempFilenameNoUri(): void
    {
        $this->buildSimpleVfsStructure();

        $engine = new StorageEngine(
            $this->buildStorageConfig(['local' => $this->buildLocalInfoDescription(true)]),
            $this->getUriParser()
        );

        $this->assertRegExp('/^\/tmp\/tmpStorageFile_[\w]*$/', $engine->tempFilename());
    }

    /**
     * @throws StorageException
     */
    public function testTempFilenameUri(): void
    {
        $this->buildSimpleVfsStructure();

        $engine = new StorageEngine(
            $this->buildStorageConfig(['local' => $this->buildLocalInfoDescription(true)]),
            $this->getUriParser()
        );

        $tmpFilePath = $engine->tempFilename('localBucket://' . static::ROOT_FILE_NAME);

        $this->assertRegExp(
            \sprintf('/^\/tmp\/%s_[\w]*$/', static::ROOT_FILE_NAME),
            $tmpFilePath
        );

        $this->assertEquals(static::ROOT_FILE_CONTENT, file_get_contents($tmpFilePath));
    }

    /**
     * @throws StorageException
     */
    public function testFileExists(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->assertTrue(
            $storageEngine->fileExists('localBucket://' . self::ROOT_FILE_NAME)
        );

        $this->assertFalse(
            $storageEngine->fileExists('localBucket://file_missed.txt')
        );
    }

    /**
     * @throws StorageException
     */
    public function testFileExistsWrongFsThrowsException(): void
    {
        $this->buildSimpleVfsStructure();

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('File system `other` was not identified');

        $this->buildStorageForFs('local')->fileExists('other://' . static::ROOT_FILE_NAME);
    }

    /**
     * @throws StorageException
     */
    public function testFileExistsWrongFormatThrowsException(): void
    {
        $this->buildSimpleVfsStructure();

        $uri = 'other-//file.txt';

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(\sprintf('No uri structure was detected in uri `%s`', $uri));

        $this->buildStorageForFs('local')->fileExists($uri);
    }

    /**
     * @throws StorageException
     */
    public function testReadFile(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->assertEquals(
            static::ROOT_FILE_CONTENT,
            $storageEngine->read('localBucket://' . static::ROOT_FILE_NAME)
        );
    }

    /**
     * @throws StorageException
     */
    public function testReadNonExistingFileThrowsException(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches('/^Unable to read file from location: file_missed.txt./');

        $storageEngine->read('localBucket://file_missed.txt');
    }

    /**
     * @throws StorageException
     */
    public function testReadStream(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->assertIsResource($storageEngine->readStream('localBucket://' . static::ROOT_FILE_NAME));
    }

    /**
     * @throws StorageException
     */
    public function testReadStreamNonExistingFileThrowsException(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches('/^Unable to read file from location: file_missed.txt./');

        $storageEngine->readStream('localBucket://file_missed.txt');
    }

    /**
     * @throws StorageException
     */
    public function testLastModified(): void
    {
        $today = new \DateTimeImmutable();

        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $fileLastModified = $storageEngine->lastModified('localBucket://' . static::ROOT_FILE_NAME);
        $dateLastModified = $today->setTimestamp($fileLastModified);

        $this->assertIsInt($fileLastModified);
        $this->assertNotEquals($today, $dateLastModified);

        $this->assertEquals($today->format('Y-m-d'), $dateLastModified->format('Y-m-d'));
    }

    /**
     * @throws StorageException
     */
    public function testLastModifiedNonExistingFileThrowsException(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to retrieve the last_modified for file at location: file_missed.txt./'
        );

        $storageEngine->lastModified('localBucket://file_missed.txt');
    }

    /**
     * @throws StorageException
     */
    public function testFileSize(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $fileSize = $storageEngine->fileSize('localBucket://' . static::ROOT_FILE_NAME);

        $this->assertIsInt($fileSize);
        $this->assertNotEmpty($fileSize);
    }

    /**
     * @throws StorageException
     */
    public function testFileSizeNonExistingFileThrowsException(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to retrieve the file_size for file at location: file_missed.txt./'
        );

        $storageEngine->fileSize('localBucket://file_missed.txt');
    }

    /**
     * @throws StorageException
     */
    public function testMimeType(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->assertEquals('text/plain', $storageEngine->mimeType('localBucket://' . static::ROOT_FILE_NAME));
    }

    /**
     * @throws StorageException
     */
    public function testMimeTypeNonExistingFileThrowsException(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to retrieve the mime_type for file at location: file_missed.txt./'
        );

        $storageEngine->mimeType('localBucket://file_missed.txt');
    }

    /**
     * @throws StorageException
     */
    public function testVisibility(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->assertEquals('public', $storageEngine->visibility('localBucket://' . static::ROOT_FILE_NAME));
    }

    /**
     * @throws StorageException
     */
    public function testVisibilityNonExistingFileThrowsException(): void
    {
        $this->buildSimpleVfsStructure();

        $storageEngine = $this->buildStorageForFs('local');

        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessageMatches(
            '/^Unable to retrieve the visibility for file at location: file_missed.txt./'
        );

        $storageEngine->visibility('localBucket://file_missed.txt');
    }

    private function buildSimpleVfsStructure(): void
    {
        vfsStream::create(
            [
                static::ROOT_FILE_NAME => static::ROOT_FILE_CONTENT,
                static::ROOT_DIR_NAME => [
                    'dir_file.txt' => 'dir file content'
                ],
            ],
            $this->rootDir
        );
    }

    /**
     * @param string $name
     *
     * @return StorageEngine
     *
     * @throws StorageException
     */
    private function buildStorageForFs(string $name): StorageEngine
    {
        return new StorageEngine(
            $this->buildStorageConfig([$name => $this->buildLocalInfoDescription(true)]),
            $this->getUriParser()
        );
    }
}
