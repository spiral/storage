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
     *
     * @throws \Spiral\StorageEngine\Exception\UrlProcessingException
     */
    public function testParseFilePath(string $filePath, ?array $expectedArray = null): void
    {
        $this->assertEquals($expectedArray, $this->resolver->parseFilePath($filePath));
    }

    /**
     * @throws \Spiral\StorageEngine\Exception\UrlProcessingException
     */
    public function testParseFilePathWrongFormat(): void
    {
        $this->assertNull(
            $this->resolver->parseFilePath(
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
        $urlsList = $this->resolver->buildUrlsList($filesList);

        $this->assertInstanceOf(\Generator::class, $urlsList);

        $this->assertEquals($expectedUrlsList, iterator_to_array($urlsList));
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

    private function buildConfig(): StorageConfig
    {
        return new StorageConfig(
            [
                'servers' => [
                    ServerTestInterface::SERVER_NAME => [
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
