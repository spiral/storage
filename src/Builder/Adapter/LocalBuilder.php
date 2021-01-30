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
            $this->serverInfo->getOption(LocalInfo::ROOT_DIR_KEY)
        );
    }

    public function buildAdvanced(): FilesystemAdapter
    {
        $adapterClass = $this->serverInfo->getAdapterClass();

        return new $adapterClass(
            $this->serverInfo->getOption(LocalInfo::ROOT_DIR_KEY),
            $this->serverInfo->hasOption(LocalInfo::VISIBILITY_KEY)
                ? PortableVisibilityConverter::fromArray($this->serverInfo->getOption(LocalInfo::VISIBILITY_KEY))
                : null,
            $this->serverInfo->hasOption(LocalInfo::WRITE_FLAGS_KEY)
                ? $this->serverInfo->getOption(LocalInfo::WRITE_FLAGS_KEY)
                : \LOCK_EX,
            $this->serverInfo->hasOption(LocalInfo::LINK_HANDLING_KEY)
                ? $this->serverInfo->getOption(LocalInfo::LINK_HANDLING_KEY)
                : $adapterClass::DISALLOW_LINKS,
        );
    }
}
