<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

interface ServerInfoInterface
{
    public const ADAPTER_KEY = 'adapter';

    public const RESOLVER_KEY = 'resolver';

    public const VISIBILITY_KEY = 'visibility';

    /**
     * @return string
     */
    public function getAdapterClass(): string;

    /**
     * @return string
     */
    public function getResolverClass(): string;

    public function getName(): string;

    /**
     * Check if adapter for server should be configured with additional params
     *
     * @return bool
     */
    public function isAdvancedUsage(): bool;
}
