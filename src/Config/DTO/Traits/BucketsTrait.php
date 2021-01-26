<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\Traits;

use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;

trait BucketsTrait
{
    protected array $buckets = [];

    public function hasBucket(string $name): bool
    {
        return array_key_exists($name, $this->buckets);
    }

    public function getBucket(string $name): ?BucketInfo
    {
        return $this->hasBucket($name) ? $this->buckets[$name] : null;
    }

    public function constructBuckets(array $bucketsInfo, ServerInfoInterface $serverInfo): void
    {
        foreach ($bucketsInfo as $bucketName => $bucketInfo) {
            $this->addBucket(new BucketInfo($bucketName, $serverInfo, $bucketInfo));
        }
    }

    protected function addBucket(BucketInfo $bucket): self
    {
        $this->buckets[$bucket->name] = $bucket;

        return $this;
    }
}
