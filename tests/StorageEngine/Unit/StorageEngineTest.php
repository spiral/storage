<?php

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;
use Spiral\StorageEngine\StorageEngine;
use PHPUnit\Framework\TestCase;

class StorageEngineTest extends TestCase
{
    private const ROOT_DIR = '/debug/root/';

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

    private function prepareLocalFilesystem(): Filesystem
    {
        $adapter = AdapterFactory::build(
            new Local(
                'debugLocalServer',
                [
                    'class' => LocalFilesystemAdapter::class,
                    'options' => [
                        Local::ROOT_DIR_OPTION => static::ROOT_DIR,
                    ],
                ]
            )
        );

        return new Filesystem($adapter);
    }
}
