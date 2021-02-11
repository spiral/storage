<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Parser\DTO;

class UriStructure implements UriStructureInterface
{
    public string $serverPathSeparator;

    public string $server;
    public string $path;

    public function __construct(string $server, string $path, string $separator)
    {
        $this->serverPathSeparator = $separator;

        $this->server = $server;
        $this->path = $path;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s%s%s',
            $this->server,
            $this->serverPathSeparator,
            $this->path
        );
    }
}
