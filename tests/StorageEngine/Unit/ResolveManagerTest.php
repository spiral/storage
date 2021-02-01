<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AwsS3Resolver;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
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
     * @throws \ReflectionException
     */
    public function testGetResolver(): void
    {
        $serverName = 'local';

        $resolveManager = $this->buildResolveManager(
            [$serverName => $this->buildLocalInfoDescription()]
        );

        $resolver = $this->callNotPublicMethod($resolveManager, 'getResolver', [$serverName]);
        $this->assertInstanceOf(LocalSystemResolver::class, $resolver);
        $this->assertSame(
            $resolver,
            $this->callNotPublicMethod($resolveManager, 'getResolver', [$serverName])
        );
    }

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
     * @param ServerInfoInterface $serverInfo
     * @param string $expectedClass
     *
     * @throws \ReflectionException
     */
    public function testPrepareResolverByServerInfo(ServerInfoInterface $serverInfo, string $expectedClass): void
    {
        $resolveManager = $this->buildResolveManager();

        $resolver = $this->callNotPublicMethod($resolveManager, 'prepareResolverByServerInfo', [$serverInfo]);

        $this->assertInstanceOf($expectedClass, $resolver);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testPrepareResolverByUnknownAdapter(): void
    {
        $resolveManager = $this->buildResolveManager();
        $serverInfo = $this->buildLocalInfo();

        $unknownAdapter = \DateTime::class;

        $this->setProtectedProperty($serverInfo, 'class', $unknownAdapter);

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage(
            'No resolver was detected by provided adapter for server ' . $serverInfo->getName()
        );

        $this->callNotPublicMethod($resolveManager, 'prepareResolverByServerInfo', [$serverInfo]);
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
                    LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                    LocalInfo::OPTIONS_KEY => [
                        LocalInfo::ROOT_DIR_KEY => static::LOCAL_SERVER_ROOT_2,
                        LocalInfo::HOST_KEY => static::LOCAL_SERVER_HOST_2,
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

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage(
            \sprintf('File %s can\'t be identified', $uri)
        );

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
            [$this->buildLocalInfo(), LocalSystemResolver::class],
            [$this->buildAwsS3Info(), AwsS3Resolver::class]
        ];
    }

    private function buildResolveManager(?array $servers = null): ResolveManager
    {
        $filePathValidator = $this->getFilePathValidator();

        return new ResolveManager(
            $this->buildStorageConfig($servers),
            $this->getUriResolver(),
            $filePathValidator
        );
    }
}
