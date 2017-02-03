<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\LocalServer;

use Spiral\Storage\BucketInterface;
use Spiral\Storage\Entities\StorageBucket;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\Servers\LocalServer;

trait ServerTrait
{
    protected $bucket;
    protected $secondary;

    protected $server;

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            'files',
            'file:',
            ['directory' => '/'],
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
            'files-2',
            'file-2:',
            ['directory' => '/secondary/'],
            $this->getServer()
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }

    protected function getServer(): ServerInterface
    {
        return $this->server?? $this->server = new LocalServer(['home' => __DIR__ . '/fixtures/']);
    }
}