<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\UriParserInterface;
use Spiral\StorageEngine\Resolver;
use Spiral\StorageEngine\Tests\Interfaces\FsTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3FsBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalFsBuilderTrait;
use Spiral\StorageEngine\ResolveManager;

class ResolveManagerTest extends AbstractUnitTest
{
    use LocalFsBuilderTrait;
    use AwsS3FsBuilderTrait;

    private const LOCAL_SERVER_1 = 'local';
    private const LOCAL_SERVER_2 = 'local2';

    private const LOCAL_SERVER_ROOT_2 = '/some/specific/root/';
    private const LOCAL_SERVER_HOST_2 = 'http://my.images.com/';

    /**
     * @throws ConfigException
     * @throws \ReflectionException
     */
    public function testGetResolver(): void
    {
        $server = 'local';
        $bucket = 'localBucket';

        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [$server => $this->buildLocalInfoDescription()],
                [$bucket => $this->buildServerBucketInfoDesc($server)]
            ),
            $this->getUriParser()
        );

        $resolver = $this->callNotPublicMethod($resolveManager, 'getResolver', [$bucket]);
        $this->assertInstanceOf(Resolver\LocalSystemResolver::class, $resolver);
        $this->assertSame(
            $resolver,
            $this->callNotPublicMethod($resolveManager, 'getResolver', [$bucket])
        );
    }

    /**
     * @throws ConfigException
     * @throws \ReflectionException
     */
    public function testGetResolverFailed(): void
    {
        $server = 'local';

        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [$server => $this->buildLocalInfoDescription()],
            ),
            $this->getUriParser()
        );

        $missedFs = 'missedFs';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('Bucket `%s` was not found', $missedFs));

        $this->callNotPublicMethod($resolveManager, 'getResolver', [$missedFs]);
    }

    /**
     * @dataProvider getFsInfoListForResolversPrepare
     *
     * @param FileSystemInfo\FileSystemInfoInterface $fsInfo
     * @param string $expectedClass
     *
     * @throws \ReflectionException
     * @throws ConfigException
     */
    public function testPrepareResolverForFileSystem(
        FileSystemInfo\FileSystemInfoInterface $fsInfo,
        string $expectedClass
    ): void {
        $localServer = 'local';
        $awsServer = 'aws';

        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [
                    $localServer => $this->buildLocalInfoDescription(),
                    $awsServer => $this->buildAwsS3ServerDescription(),
                ],
                [
                    'localBucket' => $this->buildServerBucketInfoDesc($localServer),
                    'awsBucket' => $this->buildServerBucketInfoDesc($awsServer),
                ],
            ),
            $this->getUriParser()
        );

        $resolver = $this->callNotPublicMethod($resolveManager, 'prepareResolverForFileSystem', [$fsInfo]);

        $this->assertInstanceOf($expectedClass, $resolver);
    }

    /**
     * @dataProvider getFileLists
     *
     * @param array $filesList
     * @param array $expectedUrlsList
     *
     * @throws StorageException
     */
    public function testBuildUrlsListNoException(array $filesList, array $expectedUrlsList): void
    {
        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [
                    static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription(),
                    static::LOCAL_SERVER_2 => [
                        FileSystemInfo\LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                        FileSystemInfo\LocalInfo::OPTIONS_KEY => [
                            FileSystemInfo\LocalInfo::ROOT_DIR_KEY => static::LOCAL_SERVER_ROOT_2,
                            FileSystemInfo\LocalInfo::HOST_KEY => static::LOCAL_SERVER_HOST_2,
                        ],
                    ],
                ],
                [
                    $this->buildBucketNameByServer(static::LOCAL_SERVER_1) => $this->buildServerBucketInfoDesc(
                        static::LOCAL_SERVER_1
                    ),
                    $this->buildBucketNameByServer(static::LOCAL_SERVER_2) => $this->buildServerBucketInfoDesc(
                        static::LOCAL_SERVER_2
                    ),
                ]
            ),
            $this->getUriParser()
        );

        $urlsList = $resolveManager->buildUrlsList($filesList, false);

        $this->assertInstanceOf(\Generator::class, $urlsList);

        $this->assertEquals($expectedUrlsList, iterator_to_array($urlsList));
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlUnknownFsNoException(): void
    {
        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()],
            ),
            $this->getUriParser()
        );

        $this->assertNull(
            $resolveManager->buildUrl('unknown://someFile.txt', false)
        );
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlThrowException(): void
    {
        $uri = 'some:/+/someFile.txt';

        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()],
            ),
            $this->getUriParser()
        );

        $this->expectException(UriException::class);
        $this->expectExceptionMessage(\sprintf('No uri structure was detected in uri `%s`', $uri));

        $resolveManager->buildUrl($uri);
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlThrowableException(): void
    {
        $uri = 'local://someFile.txt';

        $exceptionMsg = 'Some unhandled exception';

        $uriParser = $this->createMock(UriParserInterface::class);
        $uriParser->expects($this->once())
            ->method('parseUri')
            ->willThrowException(new \Exception($exceptionMsg));

        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()]
            ),
            $uriParser
        );

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage($exceptionMsg);

        $resolveManager->buildUrl($uri);
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlWrongFormatNoException(): void
    {
        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()],
            ),
            $this->getUriParser()
        );

        $this->assertNull($resolveManager->buildUrl('unknown:\\/someFile.txt', false));
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlWrongFormatThrowsException(): void
    {
        $resolveManager = new ResolveManager(
            $this->buildStorageConfig(
                [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()],
            ),
            $this->getUriParser()
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Bucket `unknown` was not found');

        $resolveManager->buildUrl('unknown://someFile.txt');
    }

    /**
     * @throws ConfigException
     * @throws StorageException
     */
    public function testBuildBucketUri(): void
    {
        $serverName = 'local';
        $bucketName = 'bucket1';
        $bucketName2 = 'bucket2';

        $config = new StorageConfig([
            'servers' => [
                $serverName => $this->buildLocalInfoDescription(),
            ],
            'buckets' => [
                $bucketName => [
                    'server' => $serverName,
                    'directory' => 'b1/',
                ],
                $bucketName2 => [
                    'server' => $serverName,
                    'directory' => 'some/bucket/dir/',
                ]
            ],
        ]);

        $resolveManager = new ResolveManager($config, $this->getUriParser());

        $this->assertEquals(
            'local://b1/file.txt',
            $resolveManager->buildBucketUri($bucketName, 'file.txt')
        );

        $this->assertEquals(
            'local://b1/',
            $resolveManager->buildBucketUri($bucketName, '')
        );

        $this->assertEquals(
            'local://some/bucket/dir/dirFile.txt',
            $resolveManager->buildBucketUri($bucketName2, 'dirFile.txt')
        );
    }

    /**
     * @throws ConfigException
     * @throws StorageException
     */
    public function testBuildBucketUriUnknownBucket(): void
    {
        $serverName = 'local';
        $bucketName = 'bucket1';
        $bucketName2 = 'bucket2';

        $config = new StorageConfig([
            'servers' => [
                $serverName => $this->buildLocalInfoDescription(),
            ],
            'buckets' => [
                $bucketName => [
                    'server' => $serverName,
                    'directory' => 'b1/',
                ],
            ],
        ]);

        $resolveManager = new ResolveManager($config, $this->getUriParser());

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Bucket `bucket2` was not found');

        $resolveManager->buildBucketUri($bucketName2, 'file.txt');
    }

    public function getFileLists(): array
    {
        $fileTxt = 'file.txt';
        $specificCsvFile = 'some/specific/dir/file1.csv';

        $fs1 = $this->buildBucketNameByServer(static::LOCAL_SERVER_1);
        $fs2 = $this->buildBucketNameByServer(static::LOCAL_SERVER_2);

        return [
            [
                [
                    \sprintf('%s://%s', $fs1, $fileTxt),
                ],
                [
                    \sprintf('%s%s', FsTestInterface::CONFIG_HOST, $fileTxt),
                ]
            ],
            [
                [
                    \sprintf('%s://%s', $fs1, $fileTxt),
                    \sprintf('%s://%s', $fs1, $specificCsvFile),
                    \sprintf('%s://%s', $fs2, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', FsTestInterface::CONFIG_HOST, $fileTxt),
                    \sprintf('%s%s', FsTestInterface::CONFIG_HOST, $specificCsvFile),
                    \sprintf('%s%s', static::LOCAL_SERVER_HOST_2, $specificCsvFile),
                ]
            ],
            [
                [
                    \sprintf('%s://%s', $fs1, $fileTxt),
                    \sprintf('%s://%s', $fs1, $specificCsvFile),
                    \sprintf('%s:-/+/%s', $fs2, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', FsTestInterface::CONFIG_HOST, $fileTxt),
                    \sprintf('%s%s', FsTestInterface::CONFIG_HOST, $specificCsvFile),
                    null,
                ]
            ],
        ];
    }

    /**
     * @return array[]
     *
     * @throws StorageException
     */
    public function getFsInfoListForResolversPrepare(): array
    {
        return [
            [$this->buildLocalInfo('localBucket'), Resolver\LocalSystemResolver::class],
            [$this->buildAwsS3Info('awsBucket'), Resolver\AwsS3Resolver::class]
        ];
    }
}
