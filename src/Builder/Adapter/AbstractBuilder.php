<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;

abstract class AbstractBuilder implements AdapterBuilderInterface
{
    protected ServerInfoInterface $serverInfo;

    public function __construct(ServerInfoInterface $serverInfo)
    {
        $this->serverInfo = $serverInfo;
    }

    protected function getAdapterClass(): string
    {
        return $this->serverInfo->getClass();
    }
}