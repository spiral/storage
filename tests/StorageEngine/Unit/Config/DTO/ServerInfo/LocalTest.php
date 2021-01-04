<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalTest extends AbstractUnitTest
{
    /**
     * @throws StorageException
     */
    public function testValidate(): void
    {
        $rootDirOption = Local::ROOT_DIR_OPTION;
        $options = [
            'option1' => 'optionVal1',
            $rootDirOption => '/some/root/',
        ];

        $serverInfo = new Local(
            'someServer',
            [
                'class' => static::class,
                'options' => $options,
            ]
        );

        $this->assertEquals(static::class, $serverInfo->getClass());
        $this->assertEquals($options[$rootDirOption], $serverInfo->getOption($rootDirOption));
        $this->assertEquals($options['option1'], $serverInfo->getOption('option1'));
    }

    /**
     * @throws StorageException
     */
    public function testValidateFailed(): void
    {
        $options = ['option1' => 'optionVal1'];

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Local server needs rootDir defined');

        new Local(
            'someServer',
            [
                'class' => static::class,
                'options' => $options,
            ]
        );
    }
}
