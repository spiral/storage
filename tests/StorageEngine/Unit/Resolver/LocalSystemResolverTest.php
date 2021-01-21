<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalSystemResolverTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use AwsS3ServerBuilderTrait;

    /**
     * @throws StorageException
     */
    public function testWrongServerInfo(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Wrong server info (%s) for resolver %s',
                AwsS3Info::class,
                LocalSystemResolver::class
            )
        );

        new LocalSystemResolver(
            $this->buildAwsS3Info()
        );
    }

    /**
     * @dataProvider getFileUrlList
     *
     * @param string $serverName
     * @param string $host
     * @param string $filePath
     * @param string $rootDir
     * @param string $expectedUrl
     *
     * @throws StorageException
     */
    public function testBuildUrl(
        string $serverName,
        string $host,
        string $rootDir,
        string $filePath,
        string $expectedUrl
    ): void {
        $resolver = new LocalSystemResolver(
            new LocalInfo($serverName, [
                LocalInfo::CLASS_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_OPTION => $rootDir,
                    LocalInfo::HOST => $host,
                ],
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
            ])
        );

        $this->assertEquals($expectedUrl, $resolver->buildUrl($filePath));
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
            new LocalInfo($serverName, [
                LocalInfo::CLASS_KEY => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => $options,
                LocalInfo::BUCKETS_KEY => [
                    $bucketName => [
                        LocalInfo::OPTIONS_KEY => [$directoryKey => $bucketDirectory]
                    ],
                ],
            ])
        );

        $this->assertEquals(
            $options[LocalInfo::ROOT_DIR_OPTION] . $bucketDirectory,
            $resolver->buildBucketPath($bucketName)
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
            new LocalInfo($serverName, [
                LocalInfo::CLASS_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => $options,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::BUCKETS_KEY => [
                    $bucketName => [
                        LocalInfo::OPTIONS_KEY => [$directoryKey => $bucketDirectory]
                    ],
                ],
            ])
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Bucket `%s` is not defined for server `%s`', $missedBucket, $serverName)
        );

        $resolver->buildBucketPath($missedBucket);
    }

    /**
     * @throws StorageException
     */
    public function testNormalizePathForServer(): void
    {
        $serverName = ServerTestInterface::SERVER_NAME;
        $fileName = 'file.txt';

        $resolver = new LocalSystemResolver(
            new LocalInfo($serverName, [
                LocalInfo::CLASS_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_OPTION => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
            ])
        );

        $this->assertEquals(
            $fileName,
            $resolver->normalizePathForServer(
                \sprintf('%s://%s', $serverName, $fileName)
            )
        );
    }

    public function getFileUrlList(): array
    {
        $fileTxt = 'file.txt';
        $specificCsvFile = '/some/specific/dir/file1.csv';

        return [
            [
                ServerTestInterface::SERVER_NAME,
                ServerTestInterface::CONFIG_HOST,
                ServerTestInterface::ROOT_DIR,
                $fileTxt,
                \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $fileTxt),
            ],
            [
                ServerTestInterface::SERVER_NAME,
                ServerTestInterface::CONFIG_HOST,
                ServerTestInterface::ROOT_DIR,
                $specificCsvFile,
                \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $specificCsvFile),
            ],
        ];
    }
}
