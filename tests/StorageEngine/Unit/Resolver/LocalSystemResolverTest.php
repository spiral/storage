<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;
use Spiral\StorageEngine\Validation\FilePathValidator;

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

        new LocalSystemResolver($this->buildAwsS3Info(), $this->getFilePathValidator());
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
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => $rootDir,
                    LocalInfo::HOST_KEY => $host,
                ],
            ]),
            $this->getFilePathValidator()
        );

        $this->assertEquals($expectedUrl, $resolver->buildUrl($filePath));
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlNoHost(): void
    {
        $resolver = new LocalSystemResolver(
            new LocalInfo('someServer', [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => 'rootDir',
                ],
            ]),
            $this->getFilePathValidator()
        );

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage('Url can\'t be built for server someServer - host was not defined');

        $resolver->buildUrl('file1.txt');
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
            LocalInfo::ROOT_DIR_KEY => '/some/root/',
            LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
        ];

        $resolver = new LocalSystemResolver(
            new LocalInfo($serverName, [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => $options,
                LocalInfo::BUCKETS_KEY => [
                    $bucketName => [
                        LocalInfo::OPTIONS_KEY => [$directoryKey => $bucketDirectory]
                    ],
                ],
            ]),
            $this->getFilePathValidator()
        );

        $this->assertEquals(
            $options[LocalInfo::ROOT_DIR_KEY] . $bucketDirectory,
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
            LocalInfo::ROOT_DIR_KEY => '/some/root/',
            LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
        ];

        $resolver = new LocalSystemResolver(
            new LocalInfo($serverName, [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => $options,
                LocalInfo::BUCKETS_KEY => [
                    $bucketName => [
                        LocalInfo::OPTIONS_KEY => [$directoryKey => $bucketDirectory]
                    ],
                ],
            ]),
            $this->getFilePathValidator()
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Bucket `%s` is not defined for server `%s`', $missedBucket, $serverName)
        );

        $resolver->buildBucketPath($missedBucket);
    }

    /**
     * @dataProvider getFilePathListForNormalize
     *
     * @param string $filePath
     * @param string $expectedFilePath
     *
     * @throws StorageException
     */
    public function testNormalizePathForServer(string $filePath, string $expectedFilePath): void
    {
        $resolver = new LocalSystemResolver($this->buildLocalInfo(), $this->getFilePathValidator());

        $this->assertEquals($expectedFilePath, $resolver->normalizePathForServer($filePath));
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

    public function getFilePathListForNormalize(): array
    {
        $serverName = ServerTestInterface::SERVER_NAME;

        $result = [
            [
                \sprintf('%s://some/dir/%s', $serverName, 'file.txt'),
                'some/dir/file.txt',
            ],
            [
                \sprintf('%s//%s', $serverName, 'file.txt'),
                \sprintf('%s//%s', $serverName, 'file.txt'),
            ],
        ];

        $filesList = [
            'file.txt',
            'file2-.txt',
            'file_4+.gif',
            '412391*.jpg',
            'file+*(1)128121644.png',
            'file spaces-and-some-chars 2.jpg',
            'File(part 1).png',
            'File-part+2_.png',
        ];

        foreach ($filesList as $fileName) {
            $result[] = [
                \sprintf('%s://%s', $serverName, $fileName),
                $fileName,
            ];

            $result[] = [$fileName, $fileName];
        }

        return $result;
    }
}
