<?php

namespace Spiral\StorageEngine\Tests\Unit\Config;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class StorageConfigTest extends AbstractUnitTest
{
    private const CONFIG_HOST = 'http://localhost/debug/';

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
                        $this->getDriverConstKey() => AdapterName::LOCAL,
                        $this->getClassConstKey() => LocalFilesystemAdapter::class,
                        $this->getOptionsConstKey() => [
                            LocalInfo::ROOT_DIR_OPTION => $rootDir,
                            LocalInfo::HOST => static::CONFIG_HOST,
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
                        $this->getDriverConstKey() => AdapterName::LOCAL,
                        $this->getClassConstKey() => LocalFilesystemAdapter::class,
                        $this->getOptionsConstKey() => [
                            LocalInfo::ROOT_DIR_OPTION => $rootDir,
                            LocalInfo::HOST => static::CONFIG_HOST,
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
                        $this->getClassConstKey() => LocalFilesystemAdapter::class,
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Server driver for %s was not identified',
                $localServer
            )
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
                        $this->getDriverConstKey() => 'missingAdapter',
                        $this->getClassConstKey() => LocalFilesystemAdapter::class,
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Server driver for %s was not identified',
                $localServer
            )
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
                        $this->getDriverConstKey() => AdapterName::LOCAL,
                        $this->getClassConstKey() => LocalFilesystemAdapter::class,
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

    /**
     * @return string
     *
     * @throws \ReflectionException
     */
    private function getDriverConstKey(): string
    {
        return $this->getProtectedConst(StorageConfig::class, 'DRIVER_KEY');
    }

    /**
     * @return string
     *
     * @throws \ReflectionException
     */
    private function getOptionsConstKey(): string
    {
        return $this->getProtectedConst(LocalInfo::class, 'OPTIONS_KEY');
    }

    /**
     * @return string
     *
     * @throws \ReflectionException
     */
    private function getClassConstKey(): string
    {
        return $this->getProtectedConst(LocalInfo::class, 'CLASS_KEY');
    }
}
