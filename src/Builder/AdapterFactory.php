<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder;

use League\Flysystem\FilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\StorageException;

class AdapterFactory
{
    public static function build(ServerInfoInterface $info): FilesystemAdapter
    {
        switch (get_class($info)) {
            default:
                throw new StorageException(
                    'Adapter can\'t be built by server info'
                );
        }
    }
}