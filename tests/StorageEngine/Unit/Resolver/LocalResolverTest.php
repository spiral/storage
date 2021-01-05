<?php

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AbstractResolver;
use Spiral\StorageEngine\Resolver\LocalResolver;
use PHPUnit\Framework\TestCase;

class LocalResolverTest extends TestCase
{
    private const CONFIG_SERVER_NAME = 'local';
    private const CONFIG_ROOT = '/debug/root/';
    private const CONFIG_HOST = 'http://localhost:8080/debug/';

    private LocalResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new LocalResolver($this->buildConfig());
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
                    \sprintf('%s://%s', self::CONFIG_SERVER_NAME, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', self::CONFIG_HOST, $specificCsvFile),
                ]
            ],
            [
                [
                    \sprintf('%s://%s', self::CONFIG_SERVER_NAME, $fileTxt),
                    \sprintf('%s://%s', self::CONFIG_SERVER_NAME, $specificCsvFile),
                ],
                [
                    \sprintf('%s%s', self::CONFIG_HOST, $fileTxt),
                    \sprintf('%s%s', self::CONFIG_HOST, $specificCsvFile),
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
                \sprintf('%s://%s', self::CONFIG_SERVER_NAME, $fileTxt),
                [
                    0 => \sprintf('%s://%s', self::CONFIG_SERVER_NAME, $fileTxt),
                    1 => self::CONFIG_SERVER_NAME,
                    2 => $fileTxt,
                    AbstractResolver::FILE_PATH_SERVER_PART => self::CONFIG_SERVER_NAME,
                    AbstractResolver::FILE_PATH_PATH_PART => $fileTxt,
                ]
            ],
            [
                \sprintf('%s://%s', self::CONFIG_SERVER_NAME, $dirFile),
                [
                    0 => \sprintf('%s://%s', self::CONFIG_SERVER_NAME, $dirFile),
                    1 => self::CONFIG_SERVER_NAME,
                    2 => $dirFile,
                    AbstractResolver::FILE_PATH_SERVER_PART => self::CONFIG_SERVER_NAME,
                    AbstractResolver::FILE_PATH_PATH_PART => $dirFile,
                ]
            ],
            [
                \sprintf('%s:\\some/wrong/format/%s', self::CONFIG_SERVER_NAME, $fileTxt),
                null
            ],
        ];
    }

    private function buildConfig(): StorageConfig
    {
        return new StorageConfig(
            [
                'servers' => [
                    self::CONFIG_SERVER_NAME => [
                        'driver' => AdapterName::LOCAL,
                        'class' => LocalFilesystemAdapter::class,
                        'options' => [
                            Local::ROOT_DIR_OPTION => self::CONFIG_ROOT,
                            Local::HOST => self::CONFIG_HOST,
                        ],
                    ],
                ],
            ]
        );
    }
}
