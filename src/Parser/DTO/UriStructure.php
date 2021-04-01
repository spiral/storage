<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage\Parser\DTO;

class UriStructure implements UriStructureInterface
{
    /**
     * Separator between filesystem name and filepath
     *
     * @var string
     */
    public $fsPathSeparator;

    /**
     * @var string
     */
    public $fileSystem;

    /**
     * @var string
     */
    public $path;

    /**
     * @param string $fileSystem
     * @param string $path
     * @param string $separator
     */
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
