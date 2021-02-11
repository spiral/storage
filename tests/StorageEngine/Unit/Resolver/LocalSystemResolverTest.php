<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\Tests\Interfaces\FsTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3FsBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalFsBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalSystemResolverTest extends AbstractUnitTest
{
    use LocalFsBuilderTrait;
    use AwsS3FsBuilderTrait;

    /**
     * @throws StorageException
     */
    public function testWrongFsInfo(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Wrong file system info (%s) for resolver %s',
                AwsS3Info::class,
                LocalSystemResolver::class
            )
        );

        new LocalSystemResolver(
            $this->getUriParser(),
            new StorageConfig(
                ['servers' => ['aws' => $this->buildAwsS3ServerDescription()]]
            ),
            'aws'
        );
    }

    /**
     * @dataProvider getFileUrlList
     *
     * @param string $serverName
     * @param string $host
     * @param string $uri
     * @param string $rootDir
     * @param string $expectedUrl
     *
     * @throws StorageException
     */
    public function testBuildUrl(
        string $serverName,
        string $host,
        string $rootDir,
        string $uri,
        string $expectedUrl
    ): void {
        $resolver = new LocalSystemResolver(
            $this->getUriParser(),
            new StorageConfig(
                [
                    'servers' => [
                        $serverName => [
                            LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                            LocalInfo::OPTIONS_KEY => [
                                LocalInfo::ROOT_DIR_KEY => $rootDir,
                                LocalInfo::HOST_KEY => $host,
                            ],
                        ],
                    ],
                ]
            ),
            $serverName
        );

        $this->assertEquals($expectedUrl, $resolver->buildUrl($uri));
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrlNoHost(): void
    {
        $resolver = new LocalSystemResolver(
            $this->getUriParser(),
            new StorageConfig(
                [
                    'servers' => [
                        'some' => [
                            LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                            LocalInfo::OPTIONS_KEY => [
                                LocalInfo::ROOT_DIR_KEY => 'rootDir',
                            ],
                        ]
                    ]
                ]
            ),
            'some'
        );

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage('Url can\'t be built for file system some - host was not defined');

        $resolver->buildUrl('file1.txt');
    }

    /**
     * @dataProvider getUriListForNormalize
     *
     * @param string $filePath
     * @param string $uri
     *
     * @throws StorageException
     */
    public function testNormalizePathForServer(string $filePath, string $uri): void
    {
        $resolver = new LocalSystemResolver(
            $this->getUriParser(),
            new StorageConfig(
                [
                    'servers' => [
                        'local' => $this->buildLocalInfoDescription(),
                    ],
                ]
            ),
            'local'
        );

        $this->assertEquals($uri, $resolver->normalizeFilePathToUri($filePath));
    }

    public function getFileUrlList(): array
    {
        $fileTxt = 'file.txt';
        $specificCsvFile = '/some/specific/dir/file1.csv';

        return [
            [
                FsTestInterface::SERVER_NAME,
                FsTestInterface::CONFIG_HOST,
                FsTestInterface::ROOT_DIR,
                $fileTxt,
                \sprintf('%s%s', FsTestInterface::CONFIG_HOST, $fileTxt),
            ],
            [
                FsTestInterface::SERVER_NAME,
                FsTestInterface::CONFIG_HOST,
                FsTestInterface::ROOT_DIR,
                $specificCsvFile,
                \sprintf('%s%s', FsTestInterface::CONFIG_HOST, $specificCsvFile),
            ],
        ];
    }

    public function getUriListForNormalize(): array
    {
        $fsName = FsTestInterface::SERVER_NAME;

        $result = [
            [
                \sprintf('%s://some/dir/%s', $fsName, 'file.txt'),
                'some/dir/file.txt',
            ],
            [
                \sprintf('%s//%s', $fsName, 'file.txt'),
                \sprintf('%s//%s', $fsName, 'file.txt'),
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
                \sprintf('%s://%s', $fsName, $fileName),
                $fileName,
            ];

            $result[] = [$fileName, $fileName];
        }

        return $result;
    }
}
