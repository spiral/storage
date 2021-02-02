<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;

interface BucketResolverInterface
{
    /**
     * Build path to bucket dir or path to defined file in bucket dir
     *
     * @param string $bucketName
     *
     * @return string|null
     */
    public function buildBucketPath(string $bucketName): ?string;

    public function getBucketInfo(string $key): ?BucketInfoInterface;
}
