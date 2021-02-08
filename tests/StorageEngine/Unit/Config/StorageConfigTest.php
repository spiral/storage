<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\OptionsBasedInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
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
     * @throws StorageException
     */
    public function testBuildServerInfoUnknownServer(): void
    {
        $anotherServer = 'another';

        $config = new StorageConfig(
            [
                'servers' => [
                    'local' => [
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

    /**
     * @throws StorageException
     */
    public function testBuildServerInfoUnknownAdapter(): void
    {
        $serverName = 'another';

        $config = new StorageConfig(
            [
                'servers' => [
                    $serverName => [
                        ServerInfoInterface::ADAPTER_KEY => \DateTime::class,
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Adapter can\'t be identified for server ' . $serverName);

        $config->buildServerInfo($serverName);
    }

    /**
     * @throws ConfigException
     */
    public function testGetServersKeys(): void
    {
        $servers = [
            'local' => [],
            'aws' => [],
        ];

        $config = new StorageConfig(['servers' => $servers]);

        $this->assertEquals(array_keys($servers), $config->getServersKeys());
    }

    /**
     * @throws ConfigException
     */
    public function testGetBucketsKeys(): void
    {
        $buckets = ['b1' => [], 'b2' => []];

        $config = new StorageConfig(
            [
                'servers' => ['local' => []],
                'buckets' => $buckets,
            ]
        );

        $this->assertEquals(array_keys($buckets), $config->getBucketsKeys());
    }

    /**
     * @throws ConfigException
     */
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
     * @throws ConfigException
     */
    public function testGetTmpDir(): void
    {
        $configBasic = new StorageConfig(
            [
                'servers' => ['local' => $this->buildLocalInfoDescription()],
            ]
        );

        $this->assertEquals(sys_get_temp_dir(), $configBasic->getTmpDir());

        $tmpDir = __DIR__;

        $config = new StorageConfig(
            [
                'servers' => [
                    'local' => [],
                ],
                'tmp-dir' => $tmpDir,
            ]
        );

        $this->assertEquals($tmpDir, $config->getTmpDir());
    }

    /**
     * @throws ConfigException
     */
    public function testConstructorWrongTmpDirThrowsException(): void
    {
        $tmpDir = '/my+=Dir/some#3Dir/tmp';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            \sprintf('Defined tmp directory `%s` was not detected', $tmpDir)
        );

        new StorageConfig(
            [
                'servers' => [
                    'local' => [],
                ],
                'tmp-dir' => $tmpDir,
            ]
        );
    }

    /**
     * @throws ConfigException
     */
    public function testConstructorNoServersThrowsException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Servers must be defined for storage work');

        new StorageConfig([]);
    }

    /**
     * @throws ConfigException
     * @throws StorageException
     */
    public function testBuildBucketInfo(): void
    {
        $localServer = 'local';
        $awsServer = 'aws';

        $localBucket1 = 'local1B';
        $localBucket2 = 'local2B';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => $this->buildLocalInfoDescription(),
                    $awsServer => $this->buildAwsS3ServerDescription(),
                ],
                'buckets' => [
                    $localBucket1 => [
                        BucketInfoInterface::SERVER_KEY => $localServer,
                        BucketInfoInterface::DIRECTORY_KEY => '/dir1',
                    ],
                    $localBucket2 => [
                        BucketInfoInterface::SERVER_KEY => $localServer,
                        BucketInfoInterface::DIRECTORY_KEY => '/dir2',
                    ]
                ],
            ]
        );

        $bucketInfo = $config->buildBucketInfo($localBucket1);
        $this->assertInstanceOf(BucketInfoInterface::class, $bucketInfo);

        $this->assertSame($bucketInfo, $config->buildBucketInfo($localBucket1));
    }

    /**
     * @throws ConfigException
     * @throws StorageException
     */
    public function testBuildBucketInfoForMissedBucket(): void
    {
        $localServer = 'local';
        $awsServer = 'aws';

        $localBucket1 = 'local1B';
        $missedBucket = 'missedB';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => $this->buildLocalInfoDescription(),
                    $awsServer => $this->buildAwsS3ServerDescription(),
                ],
                'buckets' => [
                    $localBucket1 => [
                        BucketInfoInterface::SERVER_KEY => $localServer,
                        BucketInfoInterface::DIRECTORY_KEY => '/dir1',
                    ],
                ],
            ]
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Bucket missedB was not found');

        $config->buildBucketInfo($missedBucket);
    }

    /**
     * @throws ConfigException
     * @throws StorageException
     */
    public function testGetServerBuckets(): void
    {
        $localServer = 'local';
        $awsServer = 'aws';

        $localBucket1 = 'local1B';
        $localBucket2 = 'local2B';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => $this->buildLocalInfoDescription(),
                    $awsServer => $this->buildAwsS3ServerDescription(),
                ],
                'buckets' => [
                    $localBucket1 => [
                        BucketInfoInterface::SERVER_KEY => $localServer,
                        BucketInfoInterface::DIRECTORY_KEY => '/dir1',
                    ],
                    $localBucket2 => [
                        BucketInfoInterface::SERVER_KEY => $localServer,
                        BucketInfoInterface::DIRECTORY_KEY => '/dir2',
                    ]
                ],
            ]
        );

        $this->assertEquals([], $config->getServerBuckets($awsServer));
        $this->assertEquals([$localBucket1, $localBucket2], array_keys($config->getServerBuckets($localServer)));
    }

    /**
     * @throws ConfigException
     * @throws StorageException
     */
    public function testGetServerBucketsForMissedServer(): void
    {
        $localServer = 'local';
        $local2Server = 'local2';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => $this->buildLocalInfoDescription(),
                ],
                'buckets' => [
                    'local1B' => [
                        BucketInfoInterface::SERVER_KEY => $local2Server,
                        BucketInfoInterface::DIRECTORY_KEY => '/dir1',
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Server local2 was not found');

        $config->getServerBuckets($local2Server);
    }

    public function getServersListForBuild(): array
    {
        return [
            ['local', $this->buildLocalInfoDescription(), LocalInfo::class],
            ['awsS3', $this->buildAwsS3ServerDescription(), AwsS3Info::class],
        ];
    }
}
