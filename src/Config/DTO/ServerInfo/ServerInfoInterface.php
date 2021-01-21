<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

interface ServerInfoInterface
{
    public const DRIVER_KEY = 'driver';

    public const VISIBILITY = 'visibility';

    /**
     * Get adapter class
     *
     * @return string
     */
    public function getAdapterClass(): string;

    public function getName(): string;

    public function getDriver(): string;

    /**
     * Check if adapter for server should be configured with additional params
     *
     * @return bool
     */
    public function isAdvancedUsage(): bool;
}
