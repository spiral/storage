<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage\Parser\DTO;

interface UriStructureInterface extends \Stringable
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
}
