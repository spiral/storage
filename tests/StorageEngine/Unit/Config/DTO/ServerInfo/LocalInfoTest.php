<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AdapterResolver\AwsS3Resolver;
use Spiral\StorageEngine\Resolver\AdapterResolver\LocalSystemResolver;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
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
            $hostOption => ServerTestInterface::CONFIG_HOST,
            $missedOption => 'someMissedVal',
        ];

        $serverName = 'someServer';
        $serverInfo = new LocalInfo(
            $serverName,
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => $options,
            ]
        );

        $this->assertEquals(LocalFilesystemAdapter::class, $serverInfo->getAdapterClass());
        $this->assertEquals(LocalSystemResolver::class, $serverInfo->getResolverClass());
        $this->assertEquals($serverName, $serverInfo->getName());

        foreach ($options as $optionKey => $optionVal) {
            if ($optionKey === $missedOption) {
                $this->assertNull($serverInfo->getOption($optionKey));
                continue;
            }

            $this->assertEquals($optionVal, $serverInfo->getOption($optionKey));
        }
    }

    /**
     * @throws StorageException
     */
    public function testGetResolver(): void
    {
        $serverName = 'someServer';
        $serverInfo = new LocalInfo(
            $serverName,
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                // wrong resolver but you can define any resolver
                LocalInfo::RESOLVER_KEY => AwsS3Resolver::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );

        $this->assertEquals(AwsS3Resolver::class, $serverInfo->getResolverClass());
    }

    /**
     * @dataProvider getMissedRequiredOptions
     *
     * @param string $serverName
     * @param array $options
     * @param string $exceptionMsg
     *
     * @throws StorageException
     */
    public function testValidateRequiredOptionsFailed(string $serverName, array $options, string $exceptionMsg): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($exceptionMsg);

        new LocalInfo(
            $serverName,
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
            'Option visibility defined in wrong format for server someServer, array expected'
        );

        new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/dir/',
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
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
            \sprintf('Option %s defined in wrong format for server someServer, int expected', $label)
        );

        new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/dir/',
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
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
            'someServer',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );

        $this->assertFalse($simpleLocal->isAdvancedUsage());

        $baseAdvancedUsage = new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS_KEY => LOCK_EX,
                ],
            ]
        );

        $this->assertTrue($baseAdvancedUsage->isAdvancedUsage());

        $advancedUsage = new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
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
            'someServer',
            [
                LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR_KEY => '/some/root/',
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS_KEY => '15',
                ],
            ]
        );

        $this->assertIsInt($baseAdvancedUsage->getOption(LocalInfo::WRITE_FLAGS_KEY));
    }

    public function getMissedRequiredOptions(): array
    {
        $serverName = ServerTestInterface::SERVER_NAME;

        return [
            [
                $serverName,
                [],
                'Option rootDir not detected for server ' . $serverName,
            ],
            [
                'someServer',
                [
                    LocalInfo::HOST_KEY => ServerTestInterface::CONFIG_HOST,
                ],
                'Option rootDir not detected for server someServer'
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
