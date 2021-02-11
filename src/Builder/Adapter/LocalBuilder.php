<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;

/**
 * @property FileSystemInfoInterface|LocalInfo $fsInfo
 */
class LocalBuilder extends AbstractBuilder
{
    protected const FILE_SYSTEM_INFO_CLASS = LocalInfo::class;

    public function buildSimple(): FilesystemAdapter
    {
        $adapterClass = $this->fsInfo->getAdapterClass();

        return new $adapterClass(
            $this->fsInfo->getOption(LocalInfo::ROOT_DIR_KEY)
        );
    }

    public function buildAdvanced(): FilesystemAdapter
    {
        $adapterClass = $this->fsInfo->getAdapterClass();

        return new $adapterClass(
            $this->fsInfo->getOption(LocalInfo::ROOT_DIR_KEY),
            $this->fsInfo->hasOption(LocalInfo::VISIBILITY_KEY)
                ? PortableVisibilityConverter::fromArray($this->fsInfo->getOption(LocalInfo::VISIBILITY_KEY))
                : null,
            $this->fsInfo->hasOption(LocalInfo::WRITE_FLAGS_KEY)
                ? $this->fsInfo->getOption(LocalInfo::WRITE_FLAGS_KEY)
                : \LOCK_EX,
            $this->fsInfo->hasOption(LocalInfo::LINK_HANDLING_KEY)
                ? $this->fsInfo->getOption(LocalInfo::LINK_HANDLING_KEY)
                : $adapterClass::DISALLOW_LINKS,
        );
    }
}
