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
        $config = new StorageConfig(
            [
                'servers' => [$serverName => $serverDescription],
            ]
        );

        /** @var FileSystemInfo\FileSystemInfoInterface|FileSystemInfo\OptionsBasedInterface $fs */
        $fs = $config->buildFileSystemInfo($serverName);

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
        $localFs = 'local';
        $rootDir = '/debug/root';

        $config = new StorageConfig(
            [
                'servers' => [
                    $localFs => [
                        FileSystemInfo\LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                        FileSystemInfo\LocalInfo::OPTIONS_KEY => [
                            FileSystemInfo\LocalInfo::ROOT_DIR_KEY => $rootDir,
                            FileSystemInfo\LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                        ],
                    ],
                ],
            ]
        );

        $fsInfo = $config->buildFileSystemInfo($localFs);

        $this->assertSame($fsInfo, $config->buildFileSystemInfo($localFs));
        $this->assertNotSame($fsInfo, $config->buildFileSystemInfo($localFs, true));
    }

    /**
     * @throws StorageException
     */
    public function testBuildFsInfoUnknownFs(): void
    {
        $anotherFs = 'another';

        $config = new StorageConfig(
            [
                'servers' => [
                    'local' => [
                        FileSystemInfo\FileSystemInfoInterface::ADAPTER_KEY => LocalFilesystemAdapter::class,
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Server %s was not found',
                $anotherFs
            )
        );

        $config->buildFileSystemInfo($anotherFs);
    }

    /**
     * @throws StorageException
     */
    public function testBuildFsInfoUnknownAdapter(): void
    {
        $serverName = 'another';

        $config = new StorageConfig(
            [
                'servers' => [
                    $serverName => [
                        FileSystemInfo\FileSystemInfoInterface::ADAPTER_KEY => \DateTime::class,
                    ],
                ],
            ]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Adapter can\'t be identified for file system ' . $serverName);

        $config->buildFileSystemInfo($serverName);
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

    public function getServersListForBuild(): array
    {
        return [
            ['local', $this->buildLocalInfoDescription(), FileSystemInfo\LocalInfo::class],
            ['awsS3', $this->buildAwsS3ServerDescription(), FileSystemInfo\Aws\AwsS3Info::class],
        ];
    }
}
