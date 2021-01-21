<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\StorageEngine\Config\DTO\BucketInfo;

interface BucketsBasedInterface
{
    public const BUCKETS_KEY = 'buckets';

    public function hasBucket(string $name): bool;

    public function getBucket(string $name): ?BucketInfo;
}
