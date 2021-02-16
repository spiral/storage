<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Parser\DTO;

class UriStructure implements UriStructureInterface
{
    public string $fsPathSeparator;

    public string $fileSystem;
    public string $path;

    public function __construct(string $fileSystem, string $path, string $separator)
    {
        $this->fsPathSeparator = $separator;

        $this->fileSystem = $fileSystem;
        $this->path = $path;
    }

    public function getFileSystem(): string
    {
        return $this->fileSystem;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s%s%s',
            $this->fileSystem,
            $this->fsPathSeparator,
            $this->path
        );
    }
}
