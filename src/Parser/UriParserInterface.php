<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Parser;

use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\DTO\UriStructureInterface;

interface UriParserInterface
{
    /**
     * @param string $fs
     * @param string $path
     *
     * @return UriStructureInterface
     */
    public function prepareUri(string $fs, string $path): UriStructureInterface;

    /**
     * @param string $uri
     *
     * @return UriStructureInterface
     *
     * @throws UriException
     */
    public function parseUri(string $uri): UriStructureInterface;
}