<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

interface ResolverInterface
{
    /**
     * @param string[] $files
     *
     * @return \Generator
     */
    public function buildUrlsList(array $files): \Generator;

    /**
     * Build path to bucket dir or path to defined file in bucket dir
     *
     * @param string $server
     * @param string $bucketName
     *
     * @return string|null
     */
    public function buildBucketPath(string $server, string $bucketName): ?string;
}
