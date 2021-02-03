<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\MountException;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;

class StorageEngineTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    private StorageEngine $storage;

    private FilesystemOperator $localFileSystem;

    /**
     * @throws StorageException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->localFileSystem = new Filesystem(
            AdapterFactory::build(
                $this->buildLocalInfo(ServerTestInterface::SERVER_NAME, false)
            )
        );

        $storageConfig = new StorageConfig(['servers' => []]);

        $this->storage = new StorageEngine($storageConfig, $this->getUriResolver());
        $this->storage->mountFilesystem(ServerTestInterface::SERVER_NAME, $this->localFileSystem);
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

        $storage = new StorageEngine($storageConfig, $this->getUriResolver());

        foreach ([$local1Name, $local2Name] as $key) {
            $this->assertTrue($storage->isFileSystemExists($key));
            $this->assertInstanceOf(FilesystemOperator::class, $storage->getFileSystem($key));
        }

        $this->assertFalse($storage->isFileSystemExists(ServerTestInterface::SERVER_NAME));

        $this->assertEquals([$local1Name, $local2Name], $storage->extractMountedFileSystemsKeys());
    }

    /**
     * @throws StorageException
     */
    public function testMountFileSystems(): void
    {
        $local1Name = 'local1';
        $local2Name = 'local2';

        $storageConfig = new StorageConfig(
            [
                'servers' => [
                    $local1Name => $this->buildLocalInfoDescription(),
                ],
            ]
        );

        $storage = new StorageEngine($storageConfig, $this->getUriResolver());

        $mountedFs = new Filesystem(AdapterFactory::build($this->buildLocalInfo()));
        $storage->mountFilesystem($local2Name, $mountedFs);

        $this->assertTrue($storage->isFileSystemExists($local2Name));
        $this->assertSame($mountedFs, $storage->getFileSystem($local2Name));

        $this->assertEquals([$local1Name, $local2Name], $storage->extractMountedFileSystemsKeys());

    }

    public function testIsFileSystemExists(): void
    {
        $this->assertTrue($this->storage->isFileSystemExists(ServerTestInterface::SERVER_NAME));
        $this->assertFalse($this->storage->isFileSystemExists('missed'));
    }

    public function testGetFileSystem(): void
    {
        $this->assertSame($this->localFileSystem, $this->storage->getFileSystem(ServerTestInterface::SERVER_NAME));
        $this->assertNull($this->storage->getFileSystem('missed'));
    }

    public function testExtractMountedFileSystemsKeys(): void
    {
        $this->assertEquals([ServerTestInterface::SERVER_NAME], $this->storage->extractMountedFileSystemsKeys());
    }

    /**
     * @throws StorageException
     */
    public function testMountExistingFileSystemKeyThrowsException(): void
    {
        $newFileSystem = new Filesystem(
            AdapterFactory::build(
                $this->buildLocalInfo(ServerTestInterface::SERVER_NAME, false)
            )
        );

        $this->storage->mountFilesystem(ServerTestInterface::SERVER_NAME, $newFileSystem);

        $this->assertSame($this->storage->getFileSystem(ServerTestInterface::SERVER_NAME), $this->localFileSystem);
    }

    /**
     * @dataProvider getWrongFileSystemsList
     *
     * @param array $filesystems
     * @param string $expectedMsg
     *
     * @throws StorageException
     */
    public function testMountWrongFileSystemsThrowsException(array $filesystems, string $expectedMsg): void
    {
        $this->expectException(MountException::class);
        $this->expectExceptionMessage($expectedMsg);

        $this->storage->mountFileSystems($filesystems);
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
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage(\sprintf('File %s can\'t be identified', $file));

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

        $storage = new StorageEngine($storageConfig, $this->getUriResolver());

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

    /**
     * @return \array[][]
     * @throws StorageException
     */
    public function getWrongFileSystemsList(): array
    {
        $localName = 'local';
        $localFs = new Filesystem(AdapterFactory::build($this->buildLocalInfo($localName)));

        $local2Name = 'local2';
        $local2Fs = new Filesystem(AdapterFactory::build($this->buildLocalInfo($local2Name)));

        $dateTime = new \DateTimeImmutable();

        return [
            [
                [
                    5 => $local2Fs,
                ],
                \sprintf(
                    'Server %s can\'t be mounted - string required, %s received',
                    5,
                    gettype(5)
                )
            ],
            [
                [
                    'local' => $localFs,
                    null => $local2Fs,
                ],
                'Server --non-displayable-- can\'t be mounted - string required, empty val received',
            ],
            [
                [
                    'local' => $localFs,
                    $local2Name => $dateTime,
                ],
                \sprintf(
                    'Server %s can\'t be mounted - filesystem has wrong type - %s received',
                    $local2Name,
                    gettype($dateTime)
                ),
            ],
        ];
    }
}
