<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Bootloader;

use League\Flysystem\FilesystemOperator;
use Spiral\StorageEngine\Bootloader\StorageEngineBootloader;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class StorageEngineBootloaderTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBoot(): void
    {
        $serverName = 'local';

        $config = new StorageConfig(
            [
                'servers' => [
                    $serverName => $this->buildLocalInfoDescription(),
                ],
            ]
        );

        $engine = new StorageEngine($this->getUriResolver());

        $this->assertEmpty($this->getProtectedProperty($engine, 'fileSystems'));

        $bootloader = new StorageEngineBootloader($config);

        $bootloader->boot($engine);

        $this->assertNotEmpty($this->getProtectedProperty($engine, 'fileSystems'));

        $this->assertTrue($engine->isFileSystemExists($serverName));
        $this->assertInstanceOf(FilesystemOperator::class, $engine->getFileSystem($serverName));
    }
}
