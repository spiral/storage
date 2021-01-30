<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class ServerInfoTest extends AbstractUnitTest
{
    /**
     * @throws StorageException
     */
    public function testValidateNoOptionsFailed(): void
    {
        $serverName = 'someServer';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('Server %s needs options defined', $serverName));

        new LocalInfo(
            $serverName,
            [LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateNoAdapterClassFailed(): void
    {
        $serverName = 'someServer';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('Server %s needs adapter class defined', $serverName));

        new LocalInfo(
            $serverName,
            [
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );
    }
}
