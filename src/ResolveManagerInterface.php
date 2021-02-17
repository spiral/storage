<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;

interface ResolveManagerInterface
{
    /**
     * Build urls list by list of uris
     *
     * @param string[] $files
     * @param bool $throwException
     *  true - throw exception in case any url can't be built
     *  false - return null instead of url in case one url can't be built
     *
     * @return \Generator
     *
     * @throws ResolveException
     * @throws StorageException
     */
    public function buildUrlsList(array $files, bool $throwException = true): \Generator;

    /**
     * Build url by uri
     * Please do not forget to check if file exists
     *
     * @param string $uri
     * @param bool $throwException
     *  true - throw exception in case url can't be built
     *  false - return null instead of url in case url can't be built
     *
     * @return string|null
     *
     * @throws ResolveException
     * @throws StorageException
     */
    public function buildUrl(string $uri, bool $throwException = true): ?string;
}
