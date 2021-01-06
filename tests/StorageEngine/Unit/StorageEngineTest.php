<?php

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\StorageEngine;
use PHPUnit\Framework\TestCase;

class StorageEngineTest extends TestCase
{
    private const ROOT_DIR = '/debug/root/';
    private const CONFIG_HOST = 'http://localhost/debug/';

    public function testIsInitiated(): void
    {
        $engine = new StorageEngine();

        $this->assertFalse($engine->isInitiated());
    }

    public function testInit(): void
    {
        $engine = new StorageEngine();

        $engine->init(
            [
                'local' => $this->prepareLocalFilesystem(),
            ]
        );

        $this->assertTrue($engine->isInitiated());
    }

    /**
     * @return Filesystem
     *
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    private function prepareLocalFilesystem(): Filesystem
    {
        $adapter = AdapterFactory::build(
            new LocalInfo(
                'debugLocalServer',
                [
                    'class' => LocalFilesystemAdapter::class,
                    'options' => [
                        LocalInfo::ROOT_DIR_OPTION => static::ROOT_DIR,
                        LocalInfo::HOST => static::CONFIG_HOST,
                    ],
                ]
            )
        );

        return new Filesystem($adapter);
    }
}
