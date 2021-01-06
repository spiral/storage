<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalInfoTest extends AbstractUnitTest
{
    /**
     * @throws StorageException
     */
    public function testValidateSimple(): void
    {
        $rootDirOption = LocalInfo::ROOT_DIR_OPTION;
        $hostOption = LocalInfo::HOST;

        $options = [
            'option1' => 'optionVal1',
            $rootDirOption => '/some/root/',
            $hostOption => ServerTestInterface::CONFIG_HOST,
        ];

        $serverInfo = new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => $options,
            ]
        );

        $this->assertEquals(LocalFilesystemAdapter::class, $serverInfo->getClass());

        foreach ($options as $optionKey => $optionVal) {
            $this->assertEquals($optionVal, $serverInfo->getOption($optionKey));
        }
    }

    /**
     * @dataProvider getMissedRequiredOptions
     *
     * @param array $options
     * @param string $exceptionMsg
     *
     * @throws StorageException
     */
    public function testValidateRequiredOptionsFailed(array $options, string $exceptionMsg): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($exceptionMsg);

        new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => $options,
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateOptionalOptionsVisibilityFailed(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Visibility specification should be defined as array');

        new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    LocalInfo::ROOT_DIR_OPTION => '/some/dir/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                    LocalInfo::VISIBILITY => 12,
                ],
            ]
        );
    }

    /**
     * @dataProvider getOptionalIntOptions
     *
     * @param string $label
     *
     * @throws StorageException
     */
    public function testValidateOptionalOptionsWriteFlagsFailed(string $label): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            \sprintf('%s should be defined as integer', $label)
        );

        new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    LocalInfo::ROOT_DIR_OPTION => '/some/dir/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                    $label => 'MyFlag',
                ],
            ]
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBuildBucketPath(): void
    {
        $directoryKey = $this->getProtectedConst(BucketInfo::class, 'DIRECTORY_KEY');

        $bucketName = 'debugBucket';
        $bucketDirectory = 'debug/dir1/';

        $fileName = 'file.txt';

        $options = [
            LocalInfo::ROOT_DIR_OPTION => '/some/root/',
            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
        ];

        $serverInfo = new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => $options,
                'buckets' => [
                    $bucketName => [
                        'options' => [$directoryKey => $bucketDirectory]
                    ],
                ],
            ]
        );

        $this->assertInstanceOf(BucketInfo::class, $serverInfo->getBucket($bucketName));
        $this->assertEquals($bucketDirectory, $serverInfo->getBucket($bucketName)->getOption($directoryKey));

        $this->assertEquals(
            $options[LocalInfo::ROOT_DIR_OPTION] . $bucketDirectory,
            $serverInfo->buildBucketPath($bucketName)
        );

        $this->assertEquals(
            $options[LocalInfo::ROOT_DIR_OPTION] . $bucketDirectory . $fileName,
            $serverInfo->buildBucketPath($bucketName, $fileName)
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBuildBucketPathFailed(): void
    {
        $directoryKey = $this->getProtectedConst(BucketInfo::class, 'DIRECTORY_KEY');

        $bucketName = 'debugBucket';
        $bucketDirectory = 'debug/dir1/';

        $missedBucket = 'missedBucket';

        $options = [
            LocalInfo::ROOT_DIR_OPTION => '/some/root/',
            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
        ];

        $serverInfo = new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => $options,
                'buckets' => [
                    $bucketName => [
                        'options' => [$directoryKey => $bucketDirectory]
                    ],
                ],
            ]
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Bucket %s is not defined', $missedBucket)
        );

        $serverInfo->buildBucketPath($missedBucket);
    }

    /**
     * @throws StorageException
     */
    public function testIsAdvancedUsage(): void
    {
        $simpleLocal = new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    LocalInfo::ROOT_DIR_OPTION => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );

        $this->assertFalse($simpleLocal->isAdvancedUsage());

        $baseAdvancedUsage = new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    LocalInfo::ROOT_DIR_OPTION => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS => LOCK_EX,
                ],
            ]
        );

        $this->assertTrue($baseAdvancedUsage->isAdvancedUsage());

        $baseAdvancedUsage = new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    LocalInfo::ROOT_DIR_OPTION => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS => LOCK_EX,
                    LocalInfo::LINK_HANDLING => LocalFilesystemAdapter::DISALLOW_LINKS,
                    LocalInfo::VISIBILITY => [
                        'file' => [
                            'public' => 0640,
                            'private' => 0604,
                        ],
                        'dir' => [
                            'public' => 0740,
                            'private' => 7604,
                        ],
                    ],
                ],
            ]
        );

        $this->assertTrue($baseAdvancedUsage->isAdvancedUsage());
    }

    public function getMissedRequiredOptions(): array
    {
        return [
            [
                [],
                'Local server needs rootDir defined',
            ],
            [
                [
                    LocalInfo::ROOT_DIR_OPTION => '/root/',
                ],
                'Local server needs host defined for urls providing',
            ],
            [
                [
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
                'Local server needs rootDir defined'
            ]
        ];
    }

    public function getOptionalIntOptions(): array
    {
        return [
            [LocalInfo::WRITE_FLAGS],
            [LocalInfo::LINK_HANDLING]
        ];
    }
}
