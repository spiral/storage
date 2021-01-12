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
                'driver' => AdapterName::LOCAL,
                'options' => $options,
            ]
        );

        $this->assertEquals(LocalFilesystemAdapter::class, $serverInfo->getAdapterClass());

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
                'driver' => AdapterName::LOCAL,
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
                'driver' => AdapterName::LOCAL,
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
                'driver' => AdapterName::LOCAL,
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
     */
    public function testIsAdvancedUsage(): void
    {
        $simpleLocal = new LocalInfo(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'driver' => AdapterName::LOCAL,
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
                'driver' => AdapterName::LOCAL,
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
                'driver' => AdapterName::LOCAL,
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
                'local server needs rootDir defined',
            ],
            [
                [
                    LocalInfo::ROOT_DIR_OPTION => '/root/',
                ],
                'local server needs host defined for urls providing',
            ],
            [
                [
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
                'local server needs rootDir defined'
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
