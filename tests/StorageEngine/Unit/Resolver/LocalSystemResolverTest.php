<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AbstractResolver;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalSystemResolverTest extends AbstractUnitTest
{
    /**
     * @dataProvider getServerFilePathsList
     *
     * @param string $filePath
     * @param array|null $expectedArray
     */
    public function testParseFilePath(string $filePath, ?array $expectedArray = null): void
    {
        $resolver = new LocalSystemResolver($this->buildConfig());

        $this->assertEquals($expectedArray, $resolver->parseFilePath($filePath));
    }

    public function testParseFilePathWrongFormat(): void
    {
        $resolver = new LocalSystemResolver($this->buildConfig());

        $this->assertNull(
            $resolver->parseFilePath(
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
        $resolver = new LocalSystemResolver($this->buildConfig());

        $urlsList = $resolver->buildUrlsList($filesList);

        $this->assertInstanceOf(\Generator::class, $urlsList);

        $this->assertEquals($expectedUrlsList, iterator_to_array($urlsList));
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBuildBucketPath(): void
    {
        $directoryKey = $this->getProtectedConst(BucketInfo::class, 'DIRECTORY_KEY');

        $serverName = ServerTestInterface::SERVER_NAME . 1232;
        $bucketName = 'debugBucket';
        $bucketDirectory = 'debug/dir1/';

        $options = [
            LocalInfo::ROOT_DIR_OPTION => '/some/root/',
            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
        ];

        $resolver = new LocalSystemResolver(
            $this->buildConfig($serverName, [
                'class' => LocalFilesystemAdapter::class,
                'driver' => AdapterName::LOCAL,
                'options' => $options,
                'buckets' => [
                    $bucketName => [
                        'options' => [$directoryKey => $bucketDirectory]
                    ],
                ],
            ])
        );

        $this->assertEquals(
            $options[LocalInfo::ROOT_DIR_OPTION] . $bucketDirectory,
            $resolver->buildBucketPath($serverName, $bucketName)
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBuildBucketPathFailed(): void
    {
        $directoryKey = $this->getProtectedConst(BucketInfo::class, 'DIRECTORY_KEY');

        $serverName = ServerTestInterface::SERVER_NAME;
        $bucketName = 'debugBucket';
        $bucketDirectory = 'debug/dir1/';

        $missedBucket = 'missedBucket';

        $options = [
            LocalInfo::ROOT_DIR_OPTION => '/some/root/',
            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
        ];

        $resolver = new LocalSystemResolver(
            $this->buildConfig($serverName, [
                'class' => LocalFilesystemAdapter::class,
                'options' => $options,
                'driver' => AdapterName::LOCAL,
                'buckets' => [
                    $bucketName => [
                        'options' => [$directoryKey => $bucketDirectory]
                    ],
                ],
            ])
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Bucket `%s` is not defined for server `%s`', $missedBucket, $serverName)
        );

        $resolver->buildBucketPath($serverName, $missedBucket);
    }

    public function getFileLists(): array
    {
        $fileTxt = 'file.txt';
        $specificCsvFile = '/some/specific/dir/file1.csv';

        return [
            [
                [
                    \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $specificCsvFile),
                ]
            ],
            [
                [
                    \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                    \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $fileTxt),
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $specificCsvFile),
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
                    AbstractResolver::FILE_PATH_SERVER_PART => ServerTestInterface::SERVER_NAME,
                    AbstractResolver::FILE_PATH_PATH_PART => $fileTxt,
                ]
            ],
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $dirFile),
                [
                    0 => \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $dirFile),
                    1 => ServerTestInterface::SERVER_NAME,
                    2 => $dirFile,
                    AbstractResolver::FILE_PATH_SERVER_PART => ServerTestInterface::SERVER_NAME,
                    AbstractResolver::FILE_PATH_PATH_PART => $dirFile,
                ]
            ],
            [
                \sprintf('%s:\\some/wrong/format/%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                null
            ],
        ];
    }

    private function buildConfig(
        $serverName = ServerTestInterface::SERVER_NAME,
        ?array $serverInfo = null
    ): StorageConfig {
        if ($serverInfo === null) {
            $serverInfo = [
                'driver' => AdapterName::LOCAL,
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    LocalInfo::ROOT_DIR_OPTION => ServerTestInterface::ROOT_DIR,
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ];
        }

        return new StorageConfig(
            [
                'servers' => [$serverName => $serverInfo],
            ]
        );
    }
}
