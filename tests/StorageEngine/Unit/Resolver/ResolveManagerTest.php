<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\ReflectionHelperTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;
use Spiral\StorageEngine\Resolver\ResolveManager;

class ResolveManagerTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use ReflectionHelperTrait;
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
     * @throws \ReflectionException
     * @throws StorageException
     */
    public function testPrepareResolverByDriver(): void
    {
        $resolveManager = $this->buildResolveManager();

        $serverInfo = $this->buildLocalInfo();

        $resolver = $this->callNotPublicMethod($resolveManager, 'prepareResolverByServerInfo', [$serverInfo]);

        $this->assertInstanceOf(LocalSystemResolver::class, $resolver);
    }

    /**
     * @dataProvider getServerFilePathsList
     *
     * @param string $filePath
     * @param array|null $expectedArray
     *
     * @throws StorageException
     */
    public function testParseFilePath(string $filePath, ?array $expectedArray = null): void
    {
        $resolveManager = $this->buildResolveManager();

        $this->assertEquals($expectedArray, $resolveManager->parseFilePath($filePath));
    }

    public function testParseFilePathWrongFormat(): void
    {
        $resolveManager = $this->buildResolveManager();

        $this->assertNull(
            $resolveManager->parseFilePath(
                \sprintf('%s//%s', ServerTestInterface::SERVER_NAME, 'file.txt')
            )
        );
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

        return [
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                [
                    0 => \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                    1 => ServerTestInterface::SERVER_NAME,
                    2 => $fileTxt,
                    ResolveManager::FILE_PATH_SERVER_PART => ServerTestInterface::SERVER_NAME,
                    ResolveManager::FILE_PATH_PATH_PART => $fileTxt,
                ]
            ],
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $dirFile),
                [
                    0 => \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $dirFile),
                    1 => ServerTestInterface::SERVER_NAME,
                    2 => $dirFile,
                    ResolveManager::FILE_PATH_SERVER_PART => ServerTestInterface::SERVER_NAME,
                    ResolveManager::FILE_PATH_PATH_PART => $dirFile,
                ]
            ],
            [
                \sprintf('%s:\\some/wrong/format/%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                null
            ],
        ];
    }

    private function buildResolveManager(?array $servers = null): ResolveManager
    {
        return new ResolveManager(
            $this->buildStorageConfig($servers)
        );
    }
}
