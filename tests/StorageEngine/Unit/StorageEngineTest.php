<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Exception\MountException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Interfaces\FsTestInterface;

/**
 * tests for basic StorageEngine methods
 */
class StorageEngineTest extends StorageEngineAbstractTest
{
    private const DEFAULT_FS = 'default';

    private StorageEngine $storage;

    private FilesystemOperator $localFileSystem;

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->localFileSystem = new Filesystem(
            AdapterFactory::build(
                $this->buildLocalInfo(FsTestInterface::SERVER_NAME, false)
            )
        );

        $storageConfig = $this->buildStorageConfig(
            [static::DEFAULT_FS => $this->buildLocalInfoDescription()]
        );

        $this->storage = new StorageEngine($storageConfig, $this->getUriParser());
        $this->mountStorageEngineFileSystem(
            $this->storage,
            $this->buildBucketNameByServer(FsTestInterface::SERVER_NAME),
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

        $fsList = [$fs1, $fs2];

        $storageConfig = new StorageConfig(
            [
                'servers' => [
                    $local1Name => $this->buildLocalInfoDescription(),
                    $local2Name => $this->buildLocalInfoDescription(),
                ],
                'buckets' => [
                    $fs1 => $this->buildServerBucketInfoDesc($local1Name),
                    $fs2 => $this->buildServerBucketInfoDesc($local2Name),
                ],
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
    public function testConstructorNoServersThrowsException(): void
    {
        $config = $this->createMock(StorageConfig::class);

        $config->expects($this->once())
            ->method('getBucketsKeys')
            ->willReturn([0]);

        $this->expectException(MountException::class);
        $this->expectExceptionMessage(
            'File system `--non-displayable--` can\'t be mounted - string required, empty val received'
        );

        new StorageEngine($config, $this->getUriParser());
    }

    /**
     * @throws StorageException
     */
    public function testMountSystemsByConfig(): void
    {
        $local1Name = 'local1';
        $local2Name = 'local2';

        $fs1 = $this->buildBucketNameByServer($local1Name);
        $fs2 = $this->buildBucketNameByServer($local2Name);

        $fsList = [$fs1, $fs2];

        $storageConfig = $this->buildStorageConfig(
            [
                $local1Name => $this->buildLocalInfoDescription(),
                $local2Name => $this->buildLocalInfoDescription(),
            ],
            [
                $fs1 => $this->buildServerBucketInfoDesc($local1Name),
                $fs2 => $this->buildServerBucketInfoDesc($local2Name),
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
                [$this->buildBucketNameByServer(FsTestInterface::SERVER_NAME)]
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
            $this->storage->getFileSystem($this->buildBucketNameByServer(FsTestInterface::SERVER_NAME))
        );
    }

    /**
     * @throws MountException
     */
    public function testGetMissedFileSystem(): void
    {
        $this->expectException(MountException::class);
        $this->expectExceptionMessage('File system `missed` was not identified');

        $this->storage->getFileSystem('missed');
    }

    public function testExtractMountedFileSystemsKeys(): void
    {
        $this->assertEquals(
            [
                $this->buildBucketNameByServer(static::DEFAULT_FS),
                $this->buildBucketNameByServer(FsTestInterface::SERVER_NAME)
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
        $bucket = $this->buildBucketNameByServer(FsTestInterface::SERVER_NAME);

        $newFileSystem = new Filesystem(
            AdapterFactory::build(
                $this->buildLocalInfo(FsTestInterface::SERVER_NAME, false)
            )
        );

        $this->mountStorageEngineFileSystem($this->storage, $bucket, $newFileSystem);

        $this->assertSame($this->storage->getFileSystem($bucket), $this->localFileSystem);
    }

    /**
     * @dataProvider getWrongFileSystemsList
     *
     * @param array $servers
     * @param string $expectedException
     * @param string $expectedMsg
     *
     * @throws StorageException
     * @throws ConfigException
     */
    /*public function testMountWrongFileSystemsThrowsException(
        array $servers,
        string $expectedException,
        string $expectedMsg
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMsg);

        $buckets = [];

        foreach ($servers as $server => $serverInfo) {
            $buckets[$this->buildBucketNameByServer($server)] = [
                'server' => $server,
                'directory' => '',
            ];
        }

        $storageConfig = new StorageConfig([
            'servers' => $servers,
            'buckets' => $buckets,
        ]);

        new StorageEngine($storageConfig, $this->getUriParser());
    }*/

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
        $this->expectExceptionMessage('File system `missed` was not identified');

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

        $storageConfig = new StorageConfig(
            [
                'servers' => [
                    $localName => $this->buildLocalInfoDescription(),
                    $local2Name => $this->buildLocalInfoDescription(),
                ],
                'buckets' => [
                    $this->buildBucketNameByServer($localName) => $this->buildServerBucketInfoDesc($localName),
                    $this->buildBucketNameByServer($local2Name) => $this->buildServerBucketInfoDesc($local2Name),
                ],
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

    /*public function getWrongFileSystemsList(): array
    {
        $local2Name = 'local2';
        $localFsDesc = $this->buildLocalInfoDescription();

        $dateTime = new \DateTimeImmutable();

        return [
            [
                [
                    5 => $localFsDesc,
                ],
                MountException::class,
                \sprintf(
                    'File system `%s` can\'t be mounted - string required, %s received',
                    5,
                    gettype(5)
                )
            ],
            [
                [
                    'local' => $localFsDesc,
                    null => $localFsDesc,
                ],
                MountException::class,
                'File system --non-displayable-- can\'t be mounted - string required, empty val received',
            ],
            [
                [
                    'local' => $localFsDesc,
                    $local2Name => $dateTime,
                ],
                ConfigException::class,
                \sprintf(
                    'File system info for `%s` was provided in wrong format, array expected, %s received',
                    $local2Name,
                    gettype($dateTime)
                ),
            ],
        ];
    }*/
}
