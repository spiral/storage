<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

class BucketInfo implements BucketInfoInterface
{
    public string $name;

    public string $server;

    protected ?string $directory = null;

    public function __construct(string $name, string $server, array $info = [])
    {
        $this->name = $name;
        $this->server = $server;

        if (array_key_exists(static::DIRECTORY_KEY, $info)) {
            $this->directory = $info[static::DIRECTORY_KEY];
        }
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getDirectory(): ?string
    {
        return $this->directory;
    }
}
