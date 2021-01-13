<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\StorageException;

abstract class AbstractBuilder implements AdapterBuilderInterface
{
    protected const SERVER_INFO_CLASS = '';

    protected ServerInfoInterface $serverInfo;

    /**
     * @param ServerInfoInterface $serverInfo
     * @throws StorageException
     */
    public function __construct(ServerInfoInterface $serverInfo)
    {
        $requiredClass = static::SERVER_INFO_CLASS;

        if (empty($requiredClass) || !$serverInfo instanceof $requiredClass) {
            throw new StorageException(
                \sprintf('Wrong server info %s provided for %s', get_class($serverInfo), static::class)
            );
        }

        $this->serverInfo = $serverInfo;
    }
}
