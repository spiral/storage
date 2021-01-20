<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AwsS3Resolver;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;
use Spiral\StorageEngine\Resolver\ResolveManager;

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
     * @throws StorageException
     */
    public function testGetResolver(): void
    {
        $resolveManager = $this->buildResolveManager(
            ['local' => $this->buildLocalInfoDescription()]
        );

        $resolveManager->initResolvers();

        $resolver = $resolveManager->getResolver('local');
        $this->assertInstanceOf(LocalSystemResolver::class, $resolver);
        $this->assertSame($resolver, $resolveManager->getResolver('local'));
    }

    /**
     * @dataProvider getFilePathListForBuild
     *
     * @param string $server
     * @param string $filePath
     * @param string $expectedFilePath
     */
    public function testBuildServerFilePath(string $server, string $filePath, string $expectedFilePath): void
    {
        $resolveManager = $this->buildResolveManager(
            ['local' => $this->buildLocalInfoDescription()]
        );

        $this->assertEquals(
            $expectedFilePath, $resolveManager->buildServerFilePath($server, $filePath)
        );
    }

    public function testGetResolverFailed(): void
    {
        $resolveManager = $this->buildResolveManager(
            ['local' => $this->buildLocalInfoDescription()]
        );

        $missedServer = 'missedServer';

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(\sprintf('No resolver was detected for server %s', $missedServer));

        $resolveManager->getResolver($missedServer);
    }

    /**
     * @dataProvider getServerInfoListForResolversPrepare
     *
     * @param ServerInfoInterface $serverInfo
     * @param string $expectedClass
     *
     * @throws \ReflectionException
     */
    public function testPrepareResolverByDriver(ServerInfoInterface $serverInfo, string $expectedClass): void
    {
        $resolveManager = $this->buildResolveManager();

        $resolver = $this->callNotPublicMethod($resolveManager, 'prepareResolverByServerInfo', [$serverInfo]);

        $this->assertInstanceOf($expectedClass, $resolver);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testPrepareResolverByUnknownDriver(): void
    {
        $resolveManager = $this->buildResolveManager();
        $serverInfo = $this->buildLocalInfo();

        $unknownDriver = 'unknownDriver';

        $this->setProtectedProperty($serverInfo, 'driver', $unknownDriver);

        $this->expectExceptionMessage('No resolver was detected for driver ' . $unknownDriver);

        $resolver = $this->callNotPublicMethod($resolveManager, 'prepareResolverByServerInfo', [$serverInfo]);
    }

    /**
     * @dataProvider getServerFilePathsList
     *
     * @param string $filePath
     * @param ServerFilePathStructure $filePathStructure
     */
    public function testParseFilePath(string $filePath, ServerFilePathStructure $filePathStructure): void
    {
        $resolveManager = $this->buildResolveManager();

        $this->assertEquals($filePathStructure, $resolveManager->parseFilePath($filePath));
    }

    /**
     * @dataProvider getFileLists
     *
     * @param array $filesList
     * @param array $expectedUrlsList
     *
     * @throws StorageException
     */
    public function testBuildUrlsList(array $filesList, array $expectedUrlsList): void
    {
        $resolveManager = $this->buildResolveManager(
            [
                static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription(),
                static::LOCAL_SERVER_2 => [
                    LocalInfo::CLASS_KEY => LocalFilesystemAdapter::class,
                    LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                    LocalInfo::OPTIONS_KEY => [
                        LocalInfo::ROOT_DIR_OPTION => static::LOCAL_SERVER_ROOT_2,
                        LocalInfo::HOST => static::LOCAL_SERVER_HOST_2,
                    ],
                ],
            ]
        );

        $resolveManager->initResolvers();

        $urlsList = $resolveManager->buildUrlsList($filesList);

        $this->assertInstanceOf(\Generator::class, $urlsList);

        $this->assertEquals($expectedUrlsList, iterator_to_array($urlsList));
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlUnknownServer(): void
    {
        $resolveManager = $this->buildResolveManager(
            [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()]
        );

        $resolveManager->initResolvers();

        $this->assertNull($resolveManager->buildUrl('unknownServer://someFile.txt'));
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlWrongFormatServer(): void
    {
        $resolveManager = $this->buildResolveManager(
            [static::LOCAL_SERVER_1 => $this->buildLocalInfoDescription()]
        );

        $resolveManager->initResolvers();

        $this->assertNull($resolveManager->buildUrl('unknownServer:\\/someFile.txt'));
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
        ];
    }

    public function getServerFilePathsList(): array
    {
        $fileTxt = 'file.txt';
        $dirFile = 'some/debug/dir/file1.csv';

        $filePathStruct1 = new ServerFilePathStructure('');
        $filePathStruct1->serverName = ServerTestInterface::SERVER_NAME;
        $filePathStruct1->filePath = $fileTxt;

        $filePathStruct2 = new ServerFilePathStructure('');
        $filePathStruct2->serverName = ServerTestInterface::SERVER_NAME;
        $filePathStruct2->filePath = $dirFile;

        return [
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                $filePathStruct1
            ],
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $dirFile),
                $filePathStruct2
            ],
            [
                \sprintf('%s:\\some/wrong/format/%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                new ServerFilePathStructure('')
            ],
        ];
    }

    public function getFilePathListForBuild(): array
    {
        return [
            [
                'local',
                'file1.txt',
                'local://file1.txt',
            ],
            [
                'aws',
                'dir/file1.txt',
                'aws://dir/file1.txt',
            ],
            [
                'ftp',
                'dir/specific/file1.txt',
                'ftp://dir/specific/file1.txt',
            ],
        ];
    }

    public function getServerInfoListForResolversPrepare(): array
    {
        return [
            [$this->buildLocalInfo(), LocalSystemResolver::class],
            [$this->buildAwsS3Info(), AwsS3Resolver::class]
        ];
    }

    private function buildResolveManager(?array $servers = null): ResolveManager
    {
        return new ResolveManager(
            $this->buildStorageConfig($servers)
        );
    }
}
