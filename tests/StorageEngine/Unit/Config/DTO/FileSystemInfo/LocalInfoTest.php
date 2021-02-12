<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\FileSystemInfo;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AwsS3Resolver;
use Spiral\StorageEngine\Resolver\LocalSystemResolver;
use Spiral\StorageEngine\Tests\Interfaces\FsTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalInfoTest extends AbstractUnitTest
{
    /**
     * @throws StorageException
     */
    public function testValidateSimple(): void
    {
        $rootDirOption = LocalInfo::ROOT_DIR_KEY;
        $hostOption = LocalInfo::HOST_KEY;

        $missedOption = 'missedOption';

        $options = [
            $rootDirOption => '/some/root/',
            $hostOption => FsTestInterface::CONFIG_HOST,
            $missedOption => 'someMissedVal',
        ];

        $fsName = 'some';
        $fsInfo = new LocalInfo(
            $fsName,
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => $options,
            ]
        );

        $this->assertEquals(LocalFilesystemAdapter::class, $fsInfo->getAdapterClass());
        $this->assertEquals(LocalSystemResolver::class, $fsInfo->getResolverClass());
        $this->assertEquals($fsName, $fsInfo->getName());

        foreach ($options as $optionKey => $optionVal) {
            if ($optionKey === $missedOption) {
                $this->assertNull($fsInfo->getOption($optionKey));
                continue;
            }

            $this->assertEquals($optionVal, $fsInfo->getOption($optionKey));
        }
    }

    /**
     * @throws StorageException
     */
    public function testGetResolver(): void
    {
        $fsInfo = new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                // wrong resolver but you can define any resolver
                LocalInfo::RESOLVER_KEY => AwsS3Resolver::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                ],
            ]
        );

        $this->assertEquals(AwsS3Resolver::class, $fsInfo->getResolverClass());
    }

    /**
     * @dataProvider getMissedRequiredOptions
     *
     * @param string $fsName
     * @param array $options
     * @param string $exceptionMsg
     *
     * @throws StorageException
     */
    public function testValidateRequiredOptionsFailed(string $fsName, array $options, string $exceptionMsg): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($exceptionMsg);

        new LocalInfo(
            $fsName,
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => $options,
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateOptionalOptionsVisibilityFailed(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            'Option `visibility` defined in wrong format for file system `some`, array expected'
        );

        new LocalInfo(
            'some',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/dir/',
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                    LocalInfo::VISIBILITY_KEY => 12,
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
            \sprintf('Option `%s` defined in wrong format for file system `some`, int expected', $label)
        );

        new LocalInfo(
            'some',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/dir/',
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                    $label => 'MyFlag',
                ],
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testIsAdvancedUsage(): void
    {
        $simpleLocal = new LocalInfo(
            'some',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                ],
            ]
        );

        $this->assertFalse($simpleLocal->isAdvancedUsage());

        $baseAdvancedUsage = new LocalInfo(
            'some',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS_KEY => LOCK_EX,
                ],
            ]
        );

        $this->assertTrue($baseAdvancedUsage->isAdvancedUsage());

        $advancedUsage = new LocalInfo(
            'some',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS_KEY => LOCK_EX,
                    LocalInfo::LINK_HANDLING_KEY => LocalFilesystemAdapter::DISALLOW_LINKS,
                    LocalInfo::VISIBILITY_KEY => [
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

        $this->assertTrue($advancedUsage->isAdvancedUsage());
    }

    /**
     * @throws StorageException
     */
    public function testIntParamsUsage(): void
    {
        $baseAdvancedUsage = new LocalInfo(
            'some',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS_KEY => '15',
                ],
            ]
        );

        $this->assertIsInt($baseAdvancedUsage->getOption(LocalInfo::WRITE_FLAGS_KEY));
    }

    public function getMissedRequiredOptions(): array
    {
        $fsName = FsTestInterface::SERVER_NAME;

        return [
            [
                $fsName,
                [],
                \sprintf('Option `rootDir` not detected for file system `%s`', $fsName),
            ],
            [
                'some',
                [
                    LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
                ],
                'Option `rootDir` not detected for file system `some`'
            ]
        ];
    }

    public function getOptionalIntOptions(): array
    {
        return [
            [LocalInfo::WRITE_FLAGS_KEY],
            [LocalInfo::LINK_HANDLING_KEY]
        ];
    }
}
