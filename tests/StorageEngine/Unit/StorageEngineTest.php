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
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;

/**
 * tests for basic StorageEngine methods
 */
class StorageEngineTest extends StorageEngineAbstractTest
{
    private const DEFAULT_SERVER_NAME = 'default';

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
                $this->buildLocalInfo(ServerTestInterface::SERVER_NAME, false)
            )
        );

        $storageConfig = $this->buildStorageConfig(
            [static::DEFAULT_SERVER_NAME => $this->buildLocalInfoDescription()]
        );

        $this->storage = new StorageEngine($storageConfig, $this->getUriParser());
        $this->mountStorageEngineFileSystem(
            $this->storage,
            ServerTestInterface::SERVER_NAME,
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

        $storageConfig = new StorageConfig(
            [
                'servers' => [
                    $local1Name => $this->buildLocalInfoDescription(),
                    $local2Name => $this->buildLocalInfoDescription(),
                ],
            ]
        );

        $storage = new StorageEngine($storageConfig, $this->getUriParser());

        foreach ([$local1Name, $local2Name] as $key) {
            $this->assertInstanceOf(FilesystemOperator::class, $storage->getFileSystem($key));
        }

        $this->assertEquals([$local1Name, $local2Name], $storage->getFileSystemsNames());
    }

    /**
     * @throws StorageException
     */
    public function testMountSystemsByConfig(): void
    {
        $local1Name = 'local1';
        $local2Name = 'local2';

        $fsList = [
            $local1Name => $this->buildLocalInfoDescription(),
            $local2Name => $this->buildLocalInfoDescription(),
        ];

        $storageConfig = $this->buildStorageConfig($fsList);

        $storage = new StorageEngine($storageConfig, $this->getUriParser());

        foreach ($fsList as $key => $fsInfoDescription) {
            $this->assertInstanceOf(FilesystemOperator::class, $storage->getFileSystem($key));
        }

        $this->assertEquals(
            [$local1Name, $local2Name],
            $storage->getFileSystemsNames()
        );
    }

    public function testIsFileSystemExists(): void
    {
        $this->assertTrue(
            $this->callNotPublicMethod(
                $this->storage,
                'isFileSystemExists',
                [ServerTestInterface::SERVER_NAME]
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
        $this->assertSame($this->localFileSystem, $this->storage->getFileSystem(ServerTestInterface::SERVER_NAME));
    }

    /**
     * @throws MountException
     */
    public function testGetMissedFileSystem(): void
    {
        $this->expectException(MountException::class);
        $this->expectExceptionMessage('Server missed was not identified');

        $this->storage->getFileSystem('missed');
    }

    public function testExtractMountedFileSystemsKeys(): void
    {
        $this->assertEquals(
            [static::DEFAULT_SERVER_NAME, ServerTestInterface::SERVER_NAME],
            $this->storage->getFileSystemsNames()
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testMountExistingFileSystemKeyThrowsException(): void
    {
        $newFileSystem = new Filesystem(
            AdapterFactory::build(
                $this->buildLocalInfo(ServerTestInterface::SERVER_NAME, false)
            )
        );

        $this->mountStorageEngineFileSystem(
            $this->storage,
            ServerTestInterface::SERVER_NAME,
            $newFileSystem
        );

        $this->assertSame($this->storage->getFileSystem(ServerTestInterface::SERVER_NAME), $this->localFileSystem);
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
    public function testMountWrongFileSystemsThrowsException(
        array $servers,
        string $expectedException,
        string $expectedMsg
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMsg);

        $storageConfig = $this->buildStorageConfig($servers);

        new StorageEngine($storageConfig, $this->getUriParser());
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
    public function testDetermineFilesystemAndPathUnknownServer(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Server missed was not identified');

        $this->callNotPublicMethod($this->storage, 'determineFilesystemAndPath', ['missed://file.txt']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testDetermineFilesystemAndPathWrongFormat(): void
    {
        $file = 'missed:/-/file.txt';
        $this->expectException(UriException::class);
        $this->expectExceptionMessage('No uri structure was detected in uri ' . $file);

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
            ]
        );

        $storage = new StorageEngine($storageConfig, $this->getUriParser());

        return [
            [
                $storage,
                'local://myDir/somefile.txt',
                $localFs,
                'myDir/somefile.txt',
            ],
            [
                $storage,
                'local2://somefile.txt',
                $local2Fs,
                'somefile.txt',
            ]
        ];
    }

    public function getWrongFileSystemsList(): array
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
                    'Server %s can\'t be mounted - string required, %s received',
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
                'Server --non-displayable-- can\'t be mounted - string required, empty val received',
            ],
            [
                [
                    'local' => $localFsDesc,
                    $local2Name => $dateTime,
                ],
                ConfigException::class,
                \sprintf(
                    'Server info for %s was provided in wrong format, array expected, %s received',
                    $local2Name,
                    gettype($dateTime)
                ),
            ],
        ];
    }
}
