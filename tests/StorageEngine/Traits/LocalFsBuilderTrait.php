<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Traits;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\FsTestInterface;

trait LocalFsBuilderTrait
{
    /**
     * @param bool|null $useVcsPrefix
     *
     * @return Filesystem
     *
     * @throws StorageException
     */
    protected function buildLocalFs(?bool $useVcsPrefix = false): Filesystem
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
        return AdapterFactory::build($this->buildLocalInfo(FsTestInterface::SERVER_NAME, $useVcsPrefix));
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
        ?string $name = FsTestInterface::SERVER_NAME,
        ?bool $useVcsPrefix = false
    ): LocalInfo {
        return new LocalInfo($name, $this->buildLocalInfoDescription($useVcsPrefix));
    }

    protected function buildLocalInfoDescription(?bool $useVcsPrefix = false): array
    {
        return [
            LocalInfo::ADAPTER_KEY => LocalFilesystemAdapter::class,
            LocalInfo::OPTIONS_KEY => [
                LocalInfo::ROOT_DIR_KEY => ($useVcsPrefix ? FsTestInterface::VFS_PREFIX : '')
                    . FsTestInterface::ROOT_DIR_NAME,
                LocalInfo::HOST_KEY => FsTestInterface::CONFIG_HOST,
            ],
        ];
    }
}