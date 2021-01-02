<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Spiral\StorageEngine\Config\DTO\ServerInfo as ServerInfoDTO;
use Spiral\StorageEngine\Exception\StorageException;

class AdapterFactory
{
    /**
     * @param ServerInfoDTO\ServerInfoInterface $info
     *
     * @return FilesystemAdapter
     *
     * @throws StorageException
     */
    public static function build(ServerInfoDTO\ServerInfoInterface $info): FilesystemAdapter
    {
        switch (get_class($info)) {
            case ServerInfoDTO\Local::class:
                return static::processLocal($info);
            default:
                throw new StorageException(
                    'Adapter can\'t be built by server info'
                );
        }
    }

    /**
     * @param ServerInfoDTO\ServerInfoInterface|ServerInfoDTO\Local $info
     * @return FilesystemAdapter
     */
    private static function processLocal(ServerInfoDTO\ServerInfoInterface $info): FilesystemAdapter
    {
        $adapterClass = $info->getClass();
        $rootDir = $info->getOption(ServerInfoDTO\Local::ROOT_DIR_OPTION);

        if ($info->isAdvancedUsage()) {
            $adapter = new $adapterClass($rootDir);
        } else {
            $adapter = new $adapterClass(
                $rootDir,
                $info->hasOption(ServerInfoDTO\Local::VISIBILITY)
                    ? PortableVisibilityConverter::fromArray($info->getOption(ServerInfoDTO\Local::VISIBILITY))
                    : null,
                $info->hasOption(ServerInfoDTO\Local::WRITE_FLAGS)
                    ? $info->getOption(ServerInfoDTO\Local::WRITE_FLAGS)
                    : LOCK_EX,
                $info->hasOption(ServerInfoDTO\Local::LINK_HANDLING)
                    ? $info->getOption(ServerInfoDTO\Local::LINK_HANDLING)
                    : $adapterClass::DISALLOW_LINKS,
            );
        }

        return $adapter;
    }
}
