<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class ServerInfoTest extends AbstractUnitTest
{
    /**
     * @throws StorageException
     */
    public function testValidateUnknownDriverFailed(): void
    {
        $serverName = 'someServer';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('Server driver for %s was not identified', $serverName));

        new LocalInfo(
            $serverName,
            [
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => 'missedDriver',
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateNoDriverFailed(): void
    {
        $serverName = 'someServer';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('Server driver for %s was not identified', $serverName));

        new LocalInfo(
            $serverName,
            [
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );
    }

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
            [
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateNoClassFailed(): void
    {
        $serverName = 'someServer';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('Server %s needs adapter class defined', $serverName));

        new LocalInfo(
            $serverName,
            [
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );
    }
}
