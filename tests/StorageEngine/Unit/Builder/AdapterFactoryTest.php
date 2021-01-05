<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Builder;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class AdapterFactoryTest extends AbstractUnitTest
{
    private const ROOT_DIR = '/testRoot/';
    private const CONFIG_HOST = 'http://localhost/debug/';

    /**
     * @throws StorageException
     */
    public function testBuildSimpleLocalServer(): void
    {
        $info = new Local(
            'debugLocalServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    Local::ROOT_DIR_OPTION => static::ROOT_DIR,
                    Local::HOST => static::CONFIG_HOST,
                ],
            ]
        );

        $adapter = AdapterFactory::build($info);

        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBuildAdvancedLocalServer(): void
    {
        $options = [
            Local::ROOT_DIR_OPTION => static::ROOT_DIR,
            Local::HOST => static::CONFIG_HOST,
            Local::WRITE_FLAGS => LOCK_NB,
            Local::LINK_HANDLING => LocalFilesystemAdapter::SKIP_LINKS,
            Local::VISIBILITY => [
                'file' => [
                    'public' => 0777,
                    'private' => 0644,
                ],
                'dir' => [
                    'public' => 0776,
                    'private' => 0444,
                ],
            ],
        ];

        $info = new Local(
            'debugLocalServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => $options,
            ]
        );

        $adapter = AdapterFactory::build($info);

        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);


        $this->assertEquals(
            $options[Local::LINK_HANDLING],
            $this->getProtectedProperty($adapter, 'linkHandling')
        );
        $this->assertEquals(
            $options[Local::WRITE_FLAGS],
            $this->getProtectedProperty($adapter, 'writeFlags')
        );
        $this->assertEquals(
            PortableVisibilityConverter::fromArray($options[Local::VISIBILITY]),
            $this->getProtectedProperty($adapter, 'visibility')
        );
    }
}
