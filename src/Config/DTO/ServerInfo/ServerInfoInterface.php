<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

interface ServerInfoInterface
{
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

    /**
     * Get adapter class
     *
     * @return string
     */
    public function getAdapterClass(): string;

    /**
     * Check if adapter for server should be configured with additional params
     *
     * @return bool
     */
    public function isAdvancedUsage(): bool;

    /**
     * Build path to bucket dir or path to defined file in bucket dir
     *
     * @param string $bucketName
     * @param string|null $fileName
     *
     * @return string|null
     */
    public function buildBucketPath(string $bucketName, ?string $fileName): ?string;
}
