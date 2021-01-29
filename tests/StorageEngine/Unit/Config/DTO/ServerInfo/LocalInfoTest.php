<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Enum\AdapterName;
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
        $rootDirOption = LocalInfo::ROOT_DIR;
        $hostOption = LocalInfo::HOST;

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
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => $options,
            ]
        );

        $this->assertEquals(LocalFilesystemAdapter::class, $serverInfo->getAdapterClass());
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
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
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
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/dir/',
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
            \sprintf('Option %s defined in wrong format for server someServer, int expected', $label)
        );

        new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/dir/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
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
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );

        $this->assertFalse($simpleLocal->isAdvancedUsage());

        $baseAdvancedUsage = new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS => LOCK_EX,
                ],
            ]
        );

        $this->assertTrue($baseAdvancedUsage->isAdvancedUsage());

        $advancedUsage = new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/root/',
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

        $this->assertTrue($advancedUsage->isAdvancedUsage());
    }

    public function testIntParamsUsage(): void
    {
        $baseAdvancedUsage = new LocalInfo(
            'someServer',
            [
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => [
                    LocalInfo::ROOT_DIR => '/some/root/',
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                    LocalInfo::WRITE_FLAGS => '15',
                ],
            ]
        );

        $this->assertIsInt($baseAdvancedUsage->getOption(LocalInfo::WRITE_FLAGS));
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
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
                'Option rootDir not detected for server someServer'
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
