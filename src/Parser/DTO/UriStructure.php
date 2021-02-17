<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Parser\DTO;

class UriStructure implements UriStructureInterface
{
    /**
     * Separator between filesystem name and filepath
     *
     * @var string
     */
    public string $fsPathSeparator;

    public string $fileSystem;
    public string $path;

    public function __construct(string $fileSystem, string $path, string $separator)
    {
        $this->fsPathSeparator = $separator;

        $this->fileSystem = $fileSystem;
        $this->path = $path;
    }

    /**
     * @inheritDoc
     */
    public function getFileSystem(): string
    {
        return $this->fileSystem;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
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
