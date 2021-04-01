<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage\Config\DTO\FileSystemInfo;

interface FileSystemInfoInterface
{
    public const ADAPTER_KEY = 'adapter';

    public const RESOLVER_KEY = 'resolver';

    public const VISIBILITY_KEY = 'visibility';

    /**
     * Get used adapter class
     *
     * @return string
     */
    public function getAdapterClass(): string;

    /**
     * Get used resolver class
     *
     * @return string
     */
    public function getResolverClass(): string;

    /**
     * Get filesystem name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if adapter for fs should be configured with additional params
     *
     * @return bool
     */
    public function isAdvancedUsage(): bool;
}
