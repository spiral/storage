<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Integration\Builder;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;

class AdapterFactoryTest extends TestCase
{
    private const VFS_PREFIX = 'vfs://';
    private const ROOT_DIR_NAME = 'testRoot';

    private vfsStreamDirectory $rootDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = vfsStream::setup(static::ROOT_DIR_NAME, 777);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    public function testBuildLocalServer(): void
    {
        $this->checkLocalServerListing();
        $this->checkLocalServerRead();
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    private function checkLocalServerListing(): void
    {
        $fileContent = 'file text';

        $file1 = 'file1.txt';
        $file2 = 'file2.txt';

        $filesList = [
            $file1 => $fileContent,
            $file2 => $fileContent,
        ];

        $dir = 'someDir';
        $dirFile = 'file.txt';

        $dirFilesList = [
            $dirFile => $fileContent,
        ];

        vfsStream::create(
            array_merge(
                [],
                $filesList,
                [
                    $dir => $dirFilesList,
                ]
            ),
            $this->rootDir
        );

        $fileSystem = $this->buildLocalServer();

        $listContents = $fileSystem->listContents('/');

        $this->assertInstanceOf(DirectoryListing::class, $listContents);

        $filesAmount = 0;
        $dirsAmount = 0;

        foreach ($listContents as $key => $listElement) {
            if ($listElement instanceof FileAttributes) {
                $this->assertTrue($listElement->isFile());

                if ($key === 0) {
                    $this->assertEquals($file1, $listElement->path());
                } else {
                    $this->assertEquals($file2, $listElement->path());
                }

                $filesAmount++;
            } elseif ($listElement instanceof DirectoryAttributes) {
                $this->assertEquals($dir, $listElement->path());
                $this->assertTrue($listElement->isDir());

                $dirsAmount++;
            }
        }

        $this->assertEquals(\count($filesList), $filesAmount);

        $this->assertEquals(1, $dirsAmount);

        $listContents = $fileSystem->listContents('/', true);

        $this->assertInstanceOf(DirectoryListing::class, $listContents);

        $filesAmount = 0;
        $dirsAmount = 0;

        foreach ($listContents as $key => $listElement) {
            if ($listElement instanceof FileAttributes) {
                $this->assertTrue($listElement->isFile());

                if ($key !== 0 && $key !== 1) {
                    $this->assertEquals($dir . '/' . $dirFile, $listElement->path());
                }

                $filesAmount++;
            } elseif ($listElement instanceof DirectoryAttributes) {
                $this->assertEquals($dir, $listElement->path());
                $this->assertTrue($listElement->isDir());

                $dirsAmount++;
            }
        }

        $this->assertEquals((\count($filesList) + \count($dirFilesList)), $filesAmount);

        $this->assertEquals(1, $dirsAmount);
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    private function checkLocalServerRead(): void
    {
        $file = 'file.txt';
        $fileContent = 'file text';

        vfsStream::create(
            [
                $file => $fileContent,
            ],
            $this->rootDir
        );

        $fileSystem = $this->buildLocalServer();

        $this->assertEquals($fileContent, $fileSystem->read($file));
    }

    /**
     * @return Filesystem
     *
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    private function buildLocalServer(): Filesystem
    {
        $adapter = AdapterFactory::build(
            new Local(
                'debugLocalServer',
                [
                    'class' => LocalFilesystemAdapter::class,
                    'options' => [
                        Local::ROOT_DIR_OPTION => static::VFS_PREFIX . static::ROOT_DIR_NAME,
                    ],
                ]
            )
        );

        return new Filesystem($adapter);
    }
}
