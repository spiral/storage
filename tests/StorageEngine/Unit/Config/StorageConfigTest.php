<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ClassBasedInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\OptionsBasedInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class StorageConfigTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use AwsS3ServerBuilderTrait;

    /**
     * @dataProvider getServersListForBuild
     *
     * @param string $serverName
     * @param array $serverDescription
     * @param string $class
     *
     * @throws StorageException
     */
    public function testBuildServerInfo(string $serverName, array $serverDescription, string $class): void
    {
        $config = new StorageConfig(
            [
                'servers' => [$serverName => $serverDescription],
            ]
        );

        /** @var ServerInfoInterface|OptionsBasedInterface $serverInfo */
        $serverInfo = $config->buildServerInfo($serverName);

        $this->assertInstanceOf($class, $serverInfo);

        foreach ($serverDescription[OptionsBasedInterface::OPTIONS_KEY] as $optionKey => $optionVal) {
            $this->assertEquals($optionVal, $serverInfo->getOption($optionKey));
        }
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
                        ServerInfoInterface::ADAPTER_KEY => LocalFilesystemAdapter::class,
                        OptionsBasedInterface::OPTIONS_KEY => [
                            LocalInfo::ROOT_DIR_KEY => $rootDir,
                            LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
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
                        ServerInfoInterface::ADAPTER_KEY => LocalFilesystemAdapter::class,
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
                        ServerInfoInterface::ADAPTER_KEY => LocalFilesystemAdapter::class,
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
                        ServerInfoInterface::ADAPTER_KEY => LocalFilesystemAdapter::class,
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

    public function getServersListForBuild(): array
    {
        return [
            ['local', $this->buildLocalInfoDescription(), LocalInfo::class],
            ['awsS3', $this->buildAwsS3ServerDescription(), AwsS3Info::class],
        ];
    }
}
