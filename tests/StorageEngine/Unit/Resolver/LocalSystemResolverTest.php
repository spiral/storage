<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AbstractResolver;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;

class LocalSystemResolverTest extends TestCase
{
    private LocalSystemResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new LocalSystemResolver($this->buildConfig());
    }

    /**
     * @dataProvider getServerFilePathsList
     *
     * @param string $filePath
     * @param array|null $expectedArray
     */
    public function testParseFilePath(string $filePath, ?array $expectedArray = null): void
    {
        $this->assertEquals($expectedArray, $this->resolver->parseFilePath($filePath));
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
        $this->assertEquals($expectedUrlsList, $this->resolver->buildUrlsList($filesList));
    }

    public function getFileLists(): array
    {
        $fileTxt = 'file.txt';
        $specificCsvFile = '/some/specific/dir/file1.csv';

        return [
            [
                [
                    \sprintf('%s://%s', ServerTestInterface::LOCAL_SERVER_NAME, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', ServerTestInterface::CONFIG_HOST, $specificCsvFile),
                ]
            ],
            [
                [
                    \sprintf('%s://%s', ServerTestInterface::LOCAL_SERVER_NAME, $fileTxt),
                    \sprintf('%s://%s', ServerTestInterface::LOCAL_SERVER_NAME, $specificCsvFile),
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
                \sprintf('%s://%s', ServerTestInterface::LOCAL_SERVER_NAME, $fileTxt),
                [
                    0 => \sprintf('%s://%s', ServerTestInterface::LOCAL_SERVER_NAME, $fileTxt),
                    1 => ServerTestInterface::LOCAL_SERVER_NAME,
                    2 => $fileTxt,
                    AbstractResolver::FILE_PATH_SERVER_PART => ServerTestInterface::LOCAL_SERVER_NAME,
                    AbstractResolver::FILE_PATH_PATH_PART => $fileTxt,
                ]
            ],
            [
                \sprintf('%s://%s', ServerTestInterface::LOCAL_SERVER_NAME, $dirFile),
                [
                    0 => \sprintf('%s://%s', ServerTestInterface::LOCAL_SERVER_NAME, $dirFile),
                    1 => ServerTestInterface::LOCAL_SERVER_NAME,
                    2 => $dirFile,
                    AbstractResolver::FILE_PATH_SERVER_PART => ServerTestInterface::LOCAL_SERVER_NAME,
                    AbstractResolver::FILE_PATH_PATH_PART => $dirFile,
                ]
            ],
            [
                \sprintf('%s:\\some/wrong/format/%s', ServerTestInterface::LOCAL_SERVER_NAME, $fileTxt),
                null
            ],
        ];
    }

    private function buildConfig(): StorageConfig
    {
        return new StorageConfig(
            [
                'servers' => [
                    ServerTestInterface::LOCAL_SERVER_NAME => [
                        'driver' => AdapterName::LOCAL,
                        'class' => LocalFilesystemAdapter::class,
                        'options' => [
                            LocalInfo::ROOT_DIR_OPTION => ServerTestInterface::ROOT_DIR,
                            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                        ],
                    ],
                ],
            ]
        );
    }
}
