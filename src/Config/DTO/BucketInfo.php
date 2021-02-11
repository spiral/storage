<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;

class BucketInfo implements BucketInfoInterface
{
    public string $name;

    public ServerInfoInterface $serverInfo;

    protected ?string $directory = null;

    public function __construct(string $name, ServerInfoInterface $serverInfo, array $info = [])
    {
        $this->name = $name;

        $this->serverInfo = $serverInfo;

        if (array_key_exists(static::DIRECTORY_KEY, $info)) {
            $this->directory = $info[static::DIRECTORY_KEY];
        }
    }

    public function getServerKey(): string
    {
        return $this->serverInfo->getName();
    }

    public function getDirectory(): ?string
    {
        return $this->directory;
    }
}
