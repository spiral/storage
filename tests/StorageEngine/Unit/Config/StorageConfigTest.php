<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ClassBasedInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\OptionsBasedInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class StorageConfigTest extends AbstractUnitTest
{
    /**
     * @throws \ReflectionException
     * @throws StorageException
     */
    public function testBuildServerInfoForLocal(): void
    {
        $localServer = 'local';
        $rootDir = '/debug/root';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => [
                        ClassBasedInterface::CLASS_KEY => LocalFilesystemAdapter::class,
                        ServerInfoInterface::DRIVER_KEY => AdapterName::LOCAL,
                        OptionsBasedInterface::OPTIONS_KEY => [
                            LocalInfo::ROOT_DIR_OPTION => $rootDir,
                            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                        ],
                    ],
                ],
            ]
        );

        $serverInfo = $config->buildServerInfo($localServer);

        $this->assertInstanceOf(LocalInfo::class, $serverInfo);
        $this->assertEquals($rootDir, $serverInfo->getOption(LocalInfo::ROOT_DIR_OPTION));
        $this->assertFalse($serverInfo->isAdvancedUsage());
    }

    /**
     * @throws \ReflectionException
     * @throws StorageException
     */
    public function testBuildServerInfoForLocalCheckForce(): void
    {
        $localServer = 'local';
        $rootDir = '/debug/root';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => [
                        ServerInfoInterface::DRIVER_KEY => AdapterName::LOCAL,
                        ClassBasedInterface::CLASS_KEY => LocalFilesystemAdapter::class,
                        OptionsBasedInterface::OPTIONS_KEY => [
                            LocalInfo::ROOT_DIR_OPTION => $rootDir,
                            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                        ],
                    ],
                ],
            ]
        );

        $serverInfo = $config->buildServerInfo($localServer);

        $this->assertSame($serverInfo, $config->buildServerInfo($localServer));
        $this->assertNotSame($serverInfo, $config->buildServerInfo($localServer, true));
    }

    /**
     * @throws \ReflectionException
     * @throws StorageException
     */
    public function testBuildServerInfoNoDriver(): void
    {
        $localServer = 'local';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => [
                        ClassBasedInterface::CLASS_KEY => LocalFilesystemAdapter::class,
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            'Driver can\'t be identified for server ' . $localServer
        );

        $config->buildServerInfo($localServer);
    }

    /**
     * @throws \ReflectionException
     * @throws StorageException
     */
    public function testBuildServerInfoUnknownDriver(): void
    {
        $localServer = 'local';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => [
                        ServerInfoInterface::DRIVER_KEY => 'missingAdapter',
                        ClassBasedInterface::CLASS_KEY => LocalFilesystemAdapter::class,
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            'Driver can\'t be identified for server ' . $localServer
        );

        $config->buildServerInfo($localServer);
    }

    /**
     * @throws \ReflectionException
     * @throws StorageException
     */
    public function testBuildServerInfoUnknownAdapter(): void
    {
        $anotherServer = 'another';

        $config = new StorageConfig(
            [
                'servers' => [
                    'local' => [
                        ServerInfoInterface::DRIVER_KEY => AdapterName::LOCAL,
                        ClassBasedInterface::CLASS_KEY => LocalFilesystemAdapter::class,
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Server %s was not found',
                $anotherServer
            )
        );

        $config->buildServerInfo($anotherServer);
    }

    public function testGetServersKeys(): void
    {
        $servers = [
            'local' => [],
            'aws' => [],
        ];

        $config = new StorageConfig(['servers' => $servers]);

        $this->assertEquals(array_keys($servers), $config->getServersKeys());
    }

    public function testHasServer(): void
    {
        $localServer = 'local';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => [],
                ],
            ]
        );

        $this->assertTrue($config->hasServer($localServer));
        $this->assertFalse($config->hasServer('missing'));
    }
}
