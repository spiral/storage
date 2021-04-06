<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage\Parser;

use Spiral\Storage\Exception\UriException;

/**
 * An interface representing a mutable VO of FileSystem {@see UriInterface}.
 */
interface MutableUriInterface extends UriInterface
{
    /**
     * Updates a filesystem name which will be
     * returned by {@see UriInterface::getFileSystem()} method.
     *
     * @param string $fs
     * @return $this
     * @throws UriException In case of an bad URI scheme (fs name) component.
     */
    public function withFileSystem(string $fs): self;

    /**
     * Updates a filesystem path component which will be
     * returned by {@see UriInterface::getPath()} method.
     *
     * @param string $path
     * @return $this
     * @throws UriException In case of an bad URI path component.
     */
    public function withPath(string $path): self;
}

