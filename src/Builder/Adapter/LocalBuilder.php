<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;

/**
 * @property ServerInfoInterface|LocalInfo $serverInfo
 */
class LocalBuilder extends AbstractBuilder
{
    protected const SERVER_INFO_CLASS = LocalInfo::class;

    public function buildSimple(): FilesystemAdapter
    {
        $adapterClass = $this->serverInfo->getAdapterClass();

        return new $adapterClass(
            $this->serverInfo->getOption(LocalInfo::ROOT_DIR)
        );
    }

    public function buildAdvanced(): FilesystemAdapter
    {
        $adapterClass = $this->serverInfo->getAdapterClass();

        return new $adapterClass(
            $this->serverInfo->getOption(LocalInfo::ROOT_DIR),
            $this->serverInfo->hasOption(LocalInfo::VISIBILITY)
                ? PortableVisibilityConverter::fromArray($this->serverInfo->getOption(LocalInfo::VISIBILITY))
                : null,
            $this->serverInfo->hasOption(LocalInfo::WRITE_FLAGS)
                ? $this->serverInfo->getOption(LocalInfo::WRITE_FLAGS)
                : \LOCK_EX,
            $this->serverInfo->hasOption(LocalInfo::LINK_HANDLING)
                ? $this->serverInfo->getOption(LocalInfo::LINK_HANDLING)
                : $adapterClass::DISALLOW_LINKS,
        );
    }
}
