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
    /**
     * @throws StorageException
     */
    public function testValidateSimple(): void
    {
        $rootDirOption = Local::ROOT_DIR_OPTION;
        $options = [
            'option1' => 'optionVal1',
            $rootDirOption => '/some/root/',
        ];

        $serverInfo = new Local(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => $options,
            ]
        );

        $this->assertEquals(LocalFilesystemAdapter::class, $serverInfo->getClass());
        $this->assertEquals($options[$rootDirOption], $serverInfo->getOption($rootDirOption));
        $this->assertEquals($options['option1'], $serverInfo->getOption('option1'));
    }

    /**
     * @throws StorageException
     */
    public function testValidateRequiredOptionsFailed(): void
    {
        $options = ['option1' => 'optionVal1'];

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Local server needs rootDir defined');

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
        $rootDirOption = Local::ROOT_DIR_OPTION;

        $simpleLocal = new Local(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    $rootDirOption => '/some/root/',
                ],
            ]
        );

        $this->assertFalse($simpleLocal->isAdvancedUsage());

        $baseAdvancedUsage = new Local(
            'someServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    $rootDirOption => '/some/root/',
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
                    $rootDirOption => '/some/root/',
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

    public function getOptionalIntOptions(): array
    {
        return [
            [Local::WRITE_FLAGS],
            [Local::LINK_HANDLING]
        ];
    }
}
