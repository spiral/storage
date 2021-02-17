<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\FsTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3FsBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalFsBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class StorageConfigTest extends AbstractUnitTest
{
    use LocalFsBuilderTrait;
    use AwsS3FsBuilderTrait;

    public function testConstructorWrongServerKeyThrowsException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            'Server `--non-displayable--[0]` has incorrect key - string expected empty val received'
        );

        new StorageConfig(
            ['servers' => [0 => $this->buildLocalInfoDescription()]]
        );
    }

    public function testConstructorWrongBucketKeyThrowsException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            'Bucket `--non-displayable--[0]` has incorrect key - string expected empty val received'
        );

        new StorageConfig(
            [
                'servers' => ['local' => $this->buildLocalInfoDescription()],
                'buckets' => [0 => $this->buildBucketNameByServer('local')]
            ]
        );
    }

    public function testConstructorNoServersThrowsException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Servers must be defined for storage work');

        new StorageConfig(['servers' => []]);
    }

    public function testConstructorNoBucketsThrowsException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Buckets must be defined for storage work');

        new StorageConfig(
            [
                'servers' => ['local' => $this->buildLocalInfoDescription()],
                'buckets' => []
            ]
        );
    }

    /**
     * @dataProvider getServersListForBuild
     *
     * @param string $serverName
     * @param array $serverDescription
     * @param string $class
     *
     * @throws StorageException
     */
    public function testBuildFsInfo(string $serverName, array $serverDescription, string $class): void
    {
        $bucketName = $this->buildBucketNameByServer($serverName);
        $config = new StorageConfig(
            [
                'servers' => [$serverName => $serverDescription],
                'buckets' => [
                    $this->buildBucketNameByServer($serverName) => $this->buildServerBucketInfoDesc($serverName),
                ]
            ]
        );

        /** @var FileSystemInfo\FileSystemInfoInterface|FileSystemInfo\OptionsBasedInterface $fs */
        $fs = $config->buildFileSystemInfo($bucketName);

        $this->assertInstanceOf($class, $fs);

        foreach ($serverDescription[FileSystemInfo\OptionsBasedInterface::OPTIONS_KEY] as $optionKey => $optionVal) {
            $this->assertEquals($optionVal, $fs->getOption($optionKey));
        }
    }

    /**
     * @throws StorageException
     */
    public function testBuildFsInfoForLocalCheckForce(): void
    {
        $localServer = 'local';
        $rootDir = '/debug/root';

        $bucket = $this->buildBucketNameByServer($localServer);

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => [
                        FileSystemInfo\LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                        FileSystemInfo\LocalInfo::OPTIONS_KEY => [
                            FileSystemInfo\LocalInfo::ROOT_DIR_KEY => $rootDir,
                            FileSystemInfo\LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                        ],
                    ],
                ],
                'buckets' => [
                    $this->buildBucketNameByServer($localServer) => $this->buildServerBucketInfoDesc($localServer),
                ]
            ]
        );

        $fsInfo = $config->buildFileSystemInfo($bucket);

        $this->assertSame($fsInfo, $config->buildFileSystemInfo($bucket));
        $this->assertNotSame($fsInfo, $config->buildFileSystemInfo($bucket, true));
    }

    /**
     * @throws StorageException
     */
    public function testBuildFsInfoUnknownFs(): void
    {
        $localServer = 'local';
        $anotherFs = 'anotherBucket';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => [
                        FileSystemInfo\FileSystemInfoInterface::ADAPTER_KEY => LocalFilesystemAdapter::class,
                    ],
                ],
                'buckets' => [
                    $this->buildBucketNameByServer($localServer) => $this->buildServerBucketInfoDesc($localServer),
                ]
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Bucket `%s` was not found',
                $anotherFs
            )
        );

        $config->buildFileSystemInfo($anotherFs);
    }

    /**
     * @throws StorageException
     */
    public function testBuildFsInfoUnknownServer(): void
    {
        $serverName = 'local';

        $bucketName = $this->buildBucketNameByServer($serverName);

        $config = new StorageConfig(
            [
                'servers' => [$serverName => $this->buildLocalInfoDescription()],
                'buckets' => [$bucketName => $this->buildServerBucketInfoDesc('missedServer')],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Server `missedServer` info for filesystem `localBucket` was not detected');

        /** @var FileSystemInfo\FileSystemInfoInterface|FileSystemInfo\OptionsBasedInterface $fs */
        $config->buildFileSystemInfo($bucketName);
    }

    /**
     * @throws StorageException
     */
    public function testBuildFsInfoUnknownAdapter(): void
    {
        $serverName = 'another';
        $bucket = 'anotherBucket';

        $config = new StorageConfig(
            [
                'servers' => [
                    $serverName => [
                        FileSystemInfo\FileSystemInfoInterface::ADAPTER_KEY => \DateTime::class,
                    ],
                ],
                'buckets' => [$bucket => $this->buildServerBucketInfoDesc($serverName)]
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('Adapter can\'t be identified for filesystem `%s`', $bucket));

        $config->buildFileSystemInfo($bucket);
    }

    /**
     * @throws ConfigException
     */
    public function testGetServersKeys(): void
    {
        $localServer = 'local';
        $awsServer = 'aws';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => $this->buildLocalInfoDescription(),
                    $awsServer => $this->buildAwsS3ServerDescription(),
                ],
                'buckets' => [
                    $this->buildBucketNameByServer($localServer) => $this->buildServerBucketInfoDesc($localServer),
                    $this->buildBucketNameByServer($awsServer) => $this->buildServerBucketInfoDesc($awsServer),
                ]
            ]
        );

        $this->assertEquals([$localServer, $awsServer], $config->getServersKeys());
    }

    /**
     * @throws ConfigException
     */
    public function testGetBucketsKeys(): void
    {
        $localServer = 'local';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localServer => $this->buildLocalInfoDescription(),
                ],
                'buckets' => [
                    'b1' => $this->buildServerBucketInfoDesc($localServer),
                    'b2' => [
                        'server' => $localServer,
                        'directory' => 'tmp/specDir/',
                    ],
                ],
            ]
        );

        $this->assertEquals(['b1', 'b2'], $config->getBucketsKeys());
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
                    $localServer => $this->buildLocalInfoDescription(),
                ],
                'buckets' => [
                    $this->buildBucketNameByServer($localServer) => $this->buildServerBucketInfoDesc($localServer),
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
        $localServer = 'local';

        $configBasic = new StorageConfig(
            [
                'servers' => [
                    $localServer => $this->buildLocalInfoDescription(),
                ],
                'buckets' => [
                    $this->buildBucketNameByServer($localServer) => $this->buildServerBucketInfoDesc($localServer),
                ],
            ]
        );

        $this->assertEquals(sys_get_temp_dir(), $configBasic->getTmpDir());

        $tmpDir = __DIR__;
        $server = 'local';

        $config = new StorageConfig(
            [
                'servers' => [
                    $server => [],
                ],
                'buckets' => [
                    $server . 'B' => $this->buildServerBucketInfoDesc($server),
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

        $server = 'local';
        new StorageConfig(
            [
                'servers' => [
                    $server => [],
                ],
                'buckets' => [
                    $server . 'B' => $this->buildServerBucketInfoDesc($server),
                ],
                'tmp-dir' => $tmpDir,
            ]
        );
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

        $localBucket1 = 'localB';
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
        $this->expectExceptionMessage('Bucket `missedB` was not found');

        $config->buildBucketInfo($missedBucket);
    }

    public function getServersListForBuild(): array
    {
        return [
            ['local', $this->buildLocalInfoDescription(), FileSystemInfo\LocalInfo::class],
            ['awsS3', $this->buildAwsS3ServerDescription(), FileSystemInfo\Aws\AwsS3Info::class],
        ];
    }
}
