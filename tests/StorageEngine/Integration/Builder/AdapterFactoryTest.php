<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Integration\Builder;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;

class AdapterFactoryTest extends TestCase
{
    use LocalServerBuilderTrait;

    private vfsStreamDirectory $rootDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = vfsStream::setup(ServerTestInterface::ROOT_DIR_NAME, 777);
    }

    /**
     * @throws FilesystemException
     * @throws StorageException
     */
    public function testBuildLocalServer(): void
    {
        $this->checkLocalServerListing();
        $this->checkLocalServerRead();
    }

    /**
     * @throws FilesystemException
     * @throws StorageException
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

        $fileSystem = $this->buildLocalServer(true);

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
     * @throws FilesystemException
     * @throws StorageException
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

        $fileSystem = $this->buildLocalServer(true);

        $this->assertEquals($fileContent, $fileSystem->read($file));
    }
}
