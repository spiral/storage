<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Builder;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class AdapterFactoryTest extends AbstractUnitTest
{
    use ServerBuilderTrait;

    /**
     * @throws StorageException
     */
    public function testBuildSimpleLocalServer(): void
    {
        $info = $this->buildLocalInfo();

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
            LocalInfo::ROOT_DIR_OPTION => ServerTestInterface::ROOT_DIR,
            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
            LocalInfo::WRITE_FLAGS => LOCK_NB,
            LocalInfo::LINK_HANDLING => LocalFilesystemAdapter::SKIP_LINKS,
            LocalInfo::VISIBILITY => [
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

        $info = new LocalInfo(
            'debugLocalServer',
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => $options,
            ]
        );

        $adapter = AdapterFactory::build($info);

        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);


        $this->assertEquals(
            $options[LocalInfo::LINK_HANDLING],
            $this->getProtectedProperty($adapter, 'linkHandling')
        );
        $this->assertEquals(
            $options[LocalInfo::WRITE_FLAGS],
            $this->getProtectedProperty($adapter, 'writeFlags')
        );
        $this->assertEquals(
            PortableVisibilityConverter::fromArray($options[LocalInfo::VISIBILITY]),
            $this->getProtectedProperty($adapter, 'visibility')
        );
    }
}
