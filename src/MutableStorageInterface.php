<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage;

interface MutableStorageInterface extends StorageInterface
{
    /**
     * @param string $name
     * @param BucketInterface $storage
     */
    public function add(string $name, BucketInterface $storage): void;
}
