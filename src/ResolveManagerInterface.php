<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage;

use Spiral\Storage\Exception\ResolveException;
use Spiral\Storage\Exception\StorageException;

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
     * @return iterable
     *
     * @throws ResolveException
     * @throws StorageException
     */
    public function buildUrlsList(array $files, bool $throwException = true): iterable;

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
