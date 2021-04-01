<?php

declare(strict_types=1);

namespace Spiral\Storage\Parser\DTO;

interface UriStructureInterface
{
    /**
     * Get filesystem name
     *
     * @return string
     */
    public function getFileSystem(): string;

    /**
     * Get filepath
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Build uri
     *
     * @return string
     */
    public function __toString(): string;
}
