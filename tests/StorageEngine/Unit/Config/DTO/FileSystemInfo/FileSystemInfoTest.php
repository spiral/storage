<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\FileSystemInfo;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\FsTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class FileSystemInfoTest extends AbstractUnitTest
{
    /**
     * @throws StorageException
     */
    public function testValidateNoOptionsFailed(): void
    {
        $fsName = 'some';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('File system %s needs options defined', $fsName));

        new LocalInfo(
            $fsName,
            [LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateNoAdapterClassFailed(): void
    {
        $fsName = 'some';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('File system %s needs adapter class defined', $fsName));

        new LocalInfo(
            $fsName,
            [
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                ],
            ]
        );
    }
}
