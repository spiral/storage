<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\StorageEngine\Config\DTO\BucketInfo;

interface ServerInfoInterface
{
    public const BUCKETS_KEY = 'buckets';
    public const DRIVER_KEY = 'driver';

    /**
     * Check if option was defined for server
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasOption(string $key): bool;

    /**
     * Get option in case it was defined
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOption(string $key);

    public function hasBucket(string $name): bool;

    public function getBucket(string $name): ?BucketInfo;

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
