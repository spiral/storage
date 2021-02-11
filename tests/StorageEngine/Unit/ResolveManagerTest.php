<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\UriParserInterface;
use Spiral\StorageEngine\Resolver;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;
use Spiral\StorageEngine\ResolveManager;

class ResolveManagerTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use AwsS3ServerBuilderTrait;
    use StorageConfigTrait;

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
        $serverName = 'local';

        $resolveManager = $this->buildResolveManager(
            [$serverName => $this->buildLocalInfoDescription()]
        );

        $resolver = $this->callNotPublicMethod($resolveManager, 'getResolver', [$serverName]);
        $this->assertInstanceOf(Resolver\LocalSystemResolver::class, $resolver);
        $this->assertSame(
            $resolver,
            $this->callNotPublicMethod($resolveManager, 'getResolver', [$serverName])
        );
    }

    /**
     * @throws ConfigException
     * @throws \ReflectionException
     */
    public function testGetResolverFailed(): void
    {
        $resolveManager = $this->buildResolveManager(
            ['local' => $this->buildLocalInfoDescription()]
        );

        $missedServer = 'missedServer';

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(\sprintf('Server %s was not found', $missedServer));

        $this->callNotPublicMethod($resolveManager, 'getResolver', [$missedServer]);
    }

    /**
     * @dataProvider getServerInfoListForResolversPrepare
     *
     * @param ServerInfo\ServerInfoInterface $serverInfo
     * @param string $expectedClass
     *
     * @throws \ReflectionException
     * @throws ConfigException
     */
    public function testPrepareResolverForServer(
        ServerInfo\ServerInfoInterface $serverInfo,
        string $expectedClass
    ): void {
        $resolveManager = $this->buildResolveManager(
            [
                'local' => $this->buildLocalInfoDescription(),
                'aws' => $this->buildAwsS3ServerDescription(),
            ]
        );

        $resolver = $this->callNotPublicMethod($resolveManager, 'prepareResolverForServer', [$serverInfo]);

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
        $resolveManager = $this->buildResolveManager(
            [
                static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription(),
                static::LOCAL_SERVER_2 => [
                    ServerInfo\LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                    ServerInfo\LocalInfo::OPTIONS_KEY => [
                        ServerInfo\LocalInfo::ROOT_DIR_KEY => static::LOCAL_SERVER_ROOT_2,
                        ServerInfo\LocalInfo::HOST_KEY => static::LOCAL_SERVER_HOST_2,
                    ],
                ],
            ]
        );

        $urlsList = $resolveManager->buildUrlsList($filesList, false);

        $this->assertInstanceOf(\Generator::class, $urlsList);

        $this->assertEquals($expectedUrlsList, iterator_to_array($urlsList));
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlUnknownServerNoException(): void
    {
        $resolveManager = $this->buildResolveManager(
            [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()]
        );

        $this->assertNull(
            $resolveManager->buildUrl('unknownServer://someFile.txt', false)
        );
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlThrowException(): void
    {
        $uri = 'someServer:/+/someFile.txt';

        $resolveManager = $this->buildResolveManager(
            [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()]
        );

        $this->expectException(UriException::class);
        $this->expectExceptionMessage('No uri structure was detected in uri ' . $uri);

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
            $this->buildStorageConfig([static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()]),
            $uriParser
        );

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage($exceptionMsg);

        $resolveManager->buildUrl($uri);
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlWrongFormatServerNoException(): void
    {
        $resolveManager = $this->buildResolveManager(
            [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()]
        );

        $this->assertNull($resolveManager->buildUrl('unknownServer:\\/someFile.txt', false));
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlWrongFormatServerThrowsException(): void
    {
        $resolveManager = $this->buildResolveManager(
            [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()]
        );

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Server unknownServer was not found');

        $resolveManager->buildUrl('unknownServer://someFile.txt');
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
                    'options' => [
                        'directory' => 'b1/',
                    ],
                ],
                $bucketName2 => [
                    'server' => $serverName,
                    'options' => [
                        'directory' => 'some/bucket/dir/',
                    ],
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
                    'options' => [
                        'directory' => 'b1/',
                    ],
                ],
            ],
        ]);

        $resolveManager = new ResolveManager($config, $this->getUriParser());

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Bucket bucket2 was not found');

        $resolveManager->buildBucketUri($bucketName2, 'file.txt');
    }

    public function getFileLists(): array
    {
        $fileTxt = 'file.txt';
        $specificCsvFile = 'some/specific/dir/file1.csv';

        return [
            [
                [
                    \sprintf('%s://%s', static::LOCAL_SERVER_1, $fileTxt),
                ],
                [
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $fileTxt),
                ]
            ],
            [
                [
                    \sprintf('%s://%s', static::LOCAL_SERVER_1, $fileTxt),
                    \sprintf('%s://%s', static::LOCAL_SERVER_1, $specificCsvFile),
                    \sprintf('%s://%s', static::LOCAL_SERVER_2, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $fileTxt),
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $specificCsvFile),
                    \sprintf('%s%s', static::LOCAL_SERVER_HOST_2, $specificCsvFile),
                ]
            ],
            [
                [
                    \sprintf('%s://%s', static::LOCAL_SERVER_1, $fileTxt),
                    \sprintf('%s://%s', static::LOCAL_SERVER_1, $specificCsvFile),
                    \sprintf('%s:-/+/%s', static::LOCAL_SERVER_2, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $fileTxt),
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $specificCsvFile),
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
    public function getServerInfoListForResolversPrepare(): array
    {
        return [
            [$this->buildLocalInfo('local'), Resolver\LocalSystemResolver::class],
            [$this->buildAwsS3Info('aws'), Resolver\AwsS3Resolver::class]
        ];
    }

    /**
     * @param array|null $servers
     *
     * @return ResolveManager
     *
     * @throws ConfigException
     */
    private function buildResolveManager(?array $servers = null): ResolveManager
    {
        return new ResolveManager($this->buildStorageConfig($servers), $this->getUriParser());
    }
}
