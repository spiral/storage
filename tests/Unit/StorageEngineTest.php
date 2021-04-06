<?php

declare(strict_types=1);

namespace Spiral\Tests\Storage\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Spiral\Storage\Builder\AdapterFactory;
use Spiral\Storage\Config\StorageConfig;
use Spiral\Storage\Exception\ConfigException;
use Spiral\Storage\Exception\MountException;
use Spiral\Storage\Exception\StorageException;
use Spiral\Storage\Exception\UriException;
use Spiral\Storage\StorageEngine;

/**
 * tests for basic StorageEngine methods
 */
class StorageEngineTest extends StorageEngineAbstractTest
{
    private const DEFAULT_FS = 'default';

    /**
     * @var StorageEngine
     */
    private $storage;

    /**
     * @var FilesystemOperator
     */
    private $localFileSystem;

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->localFileSystem = new Filesystem(
            AdapterFactory::build(
                $this->buildLocalInfo(self::SERVER_NAME, false)
            )
        );

        $storageConfig = $this->buildStorageConfig(
            [static::DEFAULT_FS => $this->buildLocalInfoDescription()]
        );

        $this->storage = new StorageEngine($storageConfig, $this->getUriParser());
        $this->mountStorageEngineFileSystem(
            $this->storage,
            $this->buildBucketNameByServer(self::SERVER_NAME),
            $this->localFileSystem
        );
    }

    /**
     * @throws StorageException
     */
    public function testConstructorWithFileSystems(): void
    {
        $local1Name = 'local1';
        $local2Name = 'local2';

        $fs1 = $this->buildBucketNameByServer($local1Name);
        $fs2 = $this->buildBucketNameByServer($local2Name);

        $fsList = [$this->buildBucketNameByServer($local1Name), $this->buildBucketNameByServer($local2Name)];

        $storageConfig = $this->buildStorageConfig(
            [
                $local1Name => $this->buildLocalInfoDescription(),
                $local2Name => $this->buildLocalInfoDescription(),
            ]
        );

        $storage = new StorageEngine($storageConfig, $this->getUriParser());

        foreach ($fsList as $key) {
            $this->assertInstanceOf(FilesystemOperator::class, $storage->getFileSystem($key));
        }

        $this->assertEquals($fsList, $storage->getFileSystemsNames());
    }

    /**
     * @throws StorageException
     */
    public function testMountSystemsByConfig(): void
    {
        $local1Name = 'local1';
        $local2Name = 'local2';

        $fsList = [$this->buildBucketNameByServer($local1Name), $this->buildBucketNameByServer($local2Name)];

        $storageConfig = $this->buildStorageConfig(
            [
                $local1Name => $this->buildLocalInfoDescription(),
                $local2Name => $this->buildLocalInfoDescription(),
            ]
        );

        $storage = new StorageEngine($storageConfig, $this->getUriParser());

        foreach ($fsList as $key) {
            $this->assertInstanceOf(FilesystemOperator::class, $storage->getFileSystem($key));
        }

        $this->assertEquals($fsList, $storage->getFileSystemsNames());
    }

    public function testIsFileSystemExists(): void
    {
        $this->assertTrue(
            $this->callNotPublicMethod(
                $this->storage,
                'isFileSystemExists',
                [$this->buildBucketNameByServer(self::SERVER_NAME)]
            )
        );
        $this->assertFalse(
            $this->callNotPublicMethod(
                $this->storage,
                'isFileSystemExists',
                ['missed']
            )
        );
    }

    /**
     * @throws MountException
     */
    public function testGetFileSystem(): void
    {
        $this->assertSame(
            $this->localFileSystem,
            $this->storage->getFileSystem($this->buildBucketNameByServer(self::SERVER_NAME))
        );
    }

    /**
     * @throws MountException
     */
    public function testGetMissedFileSystem(): void
    {
        $this->expectException(MountException::class);
        $this->expectExceptionMessage('Filesystem `missed` was not identified');

        $this->storage->getFileSystem('missed');
    }

    public function testExtractMountedFileSystemsKeys(): void
    {
        $this->assertEquals(
            [
                $this->buildBucketNameByServer(static::DEFAULT_FS),
                $this->buildBucketNameByServer(self::SERVER_NAME)
            ],
            $this->storage->getFileSystemsNames()
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMountExistingFileSystemKeyThrowsException(): void
    {
        $bucket = $this->buildBucketNameByServer(self::SERVER_NAME);

        $newFileSystem = new Filesystem(
            AdapterFactory::build(
                $this->buildLocalInfo(self::SERVER_NAME, false)
            )
        );

        $this->expectException(MountException::class);
        $this->expectExceptionMessage(
            \sprintf('Filesystem %s is already mounted', $bucket)
        );

        $this->mountStorageEngineFileSystem($this->storage, $bucket, $newFileSystem);

        $this->assertSame($this->storage->getFileSystem($bucket), $this->localFileSystem);
    }

    /**
     * @dataProvider getUrisInfoToDetermine
     *
     * @param StorageEngine $storage
     * @param string $uri
     * @param FilesystemOperator $filesystem
     * @param string $filePath
     *
     * @throws \ReflectionException
     */
    public function testDetermineFilesystemAndPath(
        StorageEngine $storage,
        string $uri,
        FilesystemOperator $filesystem,
        string $filePath
    ): void {
        $determined = $this->callNotPublicMethod($storage, 'determineFilesystemAndPath', [$uri]);

        $this->assertEquals($determined[0], $filesystem);
        $this->assertEquals($determined[1], $filePath);
    }

    /**
     * @throws \ReflectionException
     */
    public function testDetermineFilesystemAndPathUnknownFs(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Filesystem `missed` was not identified');

        $this->callNotPublicMethod($this->storage, 'determineFilesystemAndPath', ['missed://file.txt']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testDetermineFilesystemAndPathWrongFormat(): void
    {
        $file = 'missed:/-/file.txt';
        $this->expectException(UriException::class);
        $this->expectExceptionMessage(\sprintf('No uri structure was detected in uri `%s`', $file));

        $this->callNotPublicMethod($this->storage, 'determineFilesystemAndPath', [$file]);
    }

    /**
     * @return array[]
     *
     * @throws StorageException
     */
    public function getUrisInfoToDetermine(): array
    {
        $localName = 'local';
        $localFs = new Filesystem(AdapterFactory::build($this->buildLocalInfo($localName)));

        $local2Name = 'local2';
        $local2Fs = new Filesystem(AdapterFactory::build($this->buildLocalInfo($local2Name)));

        $storageConfig = $this->buildStorageConfig(
            [
                $localName => $this->buildLocalInfoDescription(),
                $local2Name => $this->buildLocalInfoDescription(),
            ]
        );

        $storage = new StorageEngine($storageConfig, $this->getUriParser());

        return [
            [
                $storage,
                'localBucket://myDir/somefile.txt',
                $localFs,
                'myDir/somefile.txt',
            ],
            [
                $storage,
                'local2Bucket://somefile.txt',
                $local2Fs,
                'somefile.txt',
            ]
        ];
    }
}
