<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\AmazonServer;

use Spiral\Storage\BucketInterface;
use Spiral\Storage\Entity\StorageBucket;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\Server\AmazonServer;

trait ServerTrait
{
    protected $bucket;
    protected $secondary;
    protected $server;

    public function setUp()
    {
        if (empty(env('STORAGE_AMAZON_KEY'))) {
            $this->skipped = true;
            $this->markTestSkipped('Amazon credentials are not set');
        }
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            'amazon',
            env('STORAGE_AMAZON_PREFIX'),
            [
                'bucket' => env('STORAGE_AMAZON_BUCKET'),
                'public' => false
            ],
            $this->getServer()
        );

        $bucket->setLogger($this->makeLogger());

        return $this->bucket = $bucket;
    }

    protected function secondaryBucket(): BucketInterface
    {
        if (!empty($this->secondary)) {
            return $this->secondary;
        }

        $bucket = new StorageBucket(
            'amazon-2',
            env('STORAGE_AMAZON_PREFIX_2'),
            [
                'bucket' => env('STORAGE_AMAZON_BUCKET_2'),
                'public' => false
            ],
            $this->getServer()
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }

    protected function getServer(): ServerInterface
    {
        return $this->server ?? $this->server = new AmazonServer([
                'accessKey' => env('STORAGE_AMAZON_KEY'),
                'secretKey' => env('STORAGE_AMAZON_SECRET')
            ]);
    }
}