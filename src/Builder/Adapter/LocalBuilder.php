<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Local;

class LocalBuilder extends AbstractBuilder
{
    public function buildSimple(): FilesystemAdapter
    {
        return new ($this->getAdapterClass())(
            $this->serverInfo->getOption(Local::ROOT_DIR_OPTION)
        );
    }

    public function buildAdvanced(): FilesystemAdapter
    {
        $adapterClass = $this->getAdapterClass();

        return new $adapterClass(
            $this->serverInfo->getOption(Local::ROOT_DIR_OPTION),
            $this->serverInfo->hasOption(Local::VISIBILITY)
                ? PortableVisibilityConverter::fromArray($this->serverInfo->getOption(Local::VISIBILITY))
                : null,
            $this->serverInfo->hasOption(Local::WRITE_FLAGS)
                ? $this->serverInfo->getOption(Local::WRITE_FLAGS)
                : LOCK_EX,
            $this->serverInfo->hasOption(Local::LINK_HANDLING)
                ? $this->serverInfo->getOption(Local::LINK_HANDLING)
                : $adapterClass::DISALLOW_LINKS,
        );
    }
}