<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder;

use League\Flysystem\FilesystemAdapter;
use Spiral\StorageEngine\Builder\Adapter\AdapterBuilderInterface;
use Spiral\StorageEngine\Builder\Adapter\LocalBuilder;
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
        $builder = static::detectAdapterBuilder($info);

        if ($info->isAdvancedUsage()) {
            return $builder->buildAdvanced();
        }

        return $builder->buildSimple();
    }

    /**
     * @param ServerInfoDTO\ServerInfoInterface $info
     *
     * @return AdapterBuilderInterface
     *
     * @throws StorageException
     */
    private static function detectAdapterBuilder(ServerInfoDTO\ServerInfoInterface $info): AdapterBuilderInterface
    {
        switch (get_class($info)) {
            case ServerInfoDTO\Local::class:
                return new LocalBuilder($info);
            default:
                throw new StorageException(
                    'Adapter can\'t be built by server info'
                );
        }
    }
}
