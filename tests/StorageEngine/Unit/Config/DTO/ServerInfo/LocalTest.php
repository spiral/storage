<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalTest extends AbstractUnitTest
{
    private const CONFIG_HOST = 'http://localhost/debug/';

    /**
     * @throws StorageException
     */
    public function testValidateSimple(): void
    {
        $rootDirOption = Local::ROOT_DIR_OPTION;
        $hostOption = Local::HOST;

        $options = [
            'option1' => 'optionVal1',
            $rootDirOption => '/some/root/',
            $hostOption => static::CONFIG_HOST,
        ];

        $serverInfo = new Local(
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

        new Local(
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

        new Local(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    Local::ROOT_DIR_OPTION => '/some/dir/',
                    Local::HOST => static::CONFIG_HOST,
                    Local::VISIBILITY => 12,
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

        new Local(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    Local::ROOT_DIR_OPTION => '/some/dir/',
                    Local::HOST => static::CONFIG_HOST,
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
        $simpleLocal = new Local(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    Local::ROOT_DIR_OPTION => '/some/root/',
                    Local::HOST => static::CONFIG_HOST,
                ],
            ]
        );

        $this->assertFalse($simpleLocal->isAdvancedUsage());

        $baseAdvancedUsage = new Local(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    Local::ROOT_DIR_OPTION => '/some/root/',
                    Local::HOST => static::CONFIG_HOST,
                    Local::WRITE_FLAGS => LOCK_EX,
                ],
            ]
        );

        $this->assertTrue($baseAdvancedUsage->isAdvancedUsage());

        $baseAdvancedUsage = new Local(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    Local::ROOT_DIR_OPTION => '/some/root/',
                    Local::HOST => static::CONFIG_HOST,
                    Local::WRITE_FLAGS => LOCK_EX,
                    Local::LINK_HANDLING => LocalFilesystemAdapter::DISALLOW_LINKS,
                    Local::VISIBILITY => [
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
                    Local::ROOT_DIR_OPTION => '/root/',
                ],
                'Local server needs host defined for urls providing',
            ],
            [
                [
                    Local::HOST => self::CONFIG_HOST,
                ],
                'Local server needs rootDir defined'
            ]
        ];
    }

    public function getOptionalIntOptions(): array
    {
        return [
            [Local::WRITE_FLAGS],
            [Local::LINK_HANDLING]
        ];
    }
}
