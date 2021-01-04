<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Integration\Builder;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;

class AdapterFactoryTest extends TestCase
{
    private const ROOT_DIR_NAME = 'testRoot';

    private vfsStreamDirectory $rootDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = vfsStream::setup(static::ROOT_DIR_NAME, 777);
    }

    public function testBuild(): void
    {
        $this->checkLocalServerRead();
    }

    protected function checkLocalServerRead(): void
    {
        $file = 'file.txt';
        $fileContent = 'file text';

        vfsStream::create(
            [
                'debug' => [
                    $file => $fileContent,
                ],
            ],
            $this->rootDir
        );

        $info = new Local(
            'debugLocalServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    Local::ROOT_DIR_OPTION => static::ROOT_DIR_NAME,
                ],
            ]
        );

        $adapter = AdapterFactory::build($info);

        $this->assertInstanceOf(FilesystemAdapter::class, $adapter);
        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);

        $fileSystem = new Filesystem($adapter);

        $filePath = static::ROOT_DIR_NAME . '/debug/' . $file;

        var_dump(
            pathinfo($filePath)
        );

        /*var_dump(
            substr(
                sprintf('%o', fileperms(static::ROOT_DIR_NAME . '/file.txt')),
                -4
            )
        );*/

        var_dump(
            file_get_contents(vfsStream::url($filePath))
        );

        var_dump(
            $fileSystem->listContents('debug/')->toArray()
        );

        // fails
        $this->assertEquals($fileContent, $fileSystem->read($filePath));
    }
}