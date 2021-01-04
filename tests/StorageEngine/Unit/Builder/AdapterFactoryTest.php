<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Builder;

use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;
use Spiral\StorageEngine\Exception\StorageException;

class AdapterFactoryTest extends TestCase
{
    private const ROOT_DIR = '/testRoot/';

    /**
     * @throws StorageException
     */
    public function testBuild(): void
    {
        $this->checkLocalServerRead();
    }

    /**
     * @throws StorageException
     */
    protected function checkLocalServerRead(): void
    {
        $info = new Local(
            'debugLocalServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    Local::ROOT_DIR_OPTION => static::ROOT_DIR,
                ],
            ]
        );

        $adapter = AdapterFactory::build($info);

        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);
    }
}