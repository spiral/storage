<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Traits;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;

trait ServerBuilderTrait
{
    /**
     * @param bool|null $useVcsPrefix
     *
     * @return Filesystem
     *
     * @throws StorageException
     */
    protected function buildLocalServer(?bool $useVcsPrefix = false): Filesystem
    {
        return new Filesystem(
            $this->buildLocalAdapter($useVcsPrefix)
        );
    }

    /**
     * @param bool|null $useVcsPrefix
     *
     * @return LocalFilesystemAdapter
     *
     * @throws StorageException
     */
    protected function buildLocalAdapter(?bool $useVcsPrefix = false): FilesystemAdapter
    {
        return AdapterFactory::build($this->buildLocalInfo(ServerTestInterface::LOCAL_SERVER_NAME, $useVcsPrefix));
    }

    /**
     * @param string|null $name
     * @param bool|null $useVcsPrefix
     *
     * @return LocalInfo
     *
     * @throws StorageException
     */
    protected function buildLocalInfo(
        ?string $name = ServerTestInterface::LOCAL_SERVER_NAME,
        ?bool $useVcsPrefix = false
    ): LocalInfo {
        return new LocalInfo(
            $name,
            [
                'class' => LocalFilesystemAdapter::class,
                'options' => [
                    LocalInfo::ROOT_DIR_OPTION => ($useVcsPrefix ? ServerTestInterface::VFS_PREFIX : '')
                        . ServerTestInterface::ROOT_DIR_NAME,
                    LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
                ],
            ]
        );
    }
}
