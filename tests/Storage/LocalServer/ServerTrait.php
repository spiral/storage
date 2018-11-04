<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Storage\Tests\LocalServer;

use Spiral\Storage\BucketInterface;
use Spiral\Storage\Server\LocalServer;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\StorageBucket;

trait ServerTrait
{
    protected $server;
    protected $bucket;
    protected $secondary;

    protected function getServer(): ServerInterface
    {
        return $this->server ?? $this->server = new LocalServer(['home' => self::$OPTS['home']]);
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            $this->getServer(),
            'files',
            'file:',
            ['directory' => '/']
        );

        $bucket->setLogger($this->makeLogger());

        return $this->bucket = $bucket;
    }

    protected function getSecondaryBucket(): BucketInterface
    {
        if (!empty($this->secondary)) {
            return $this->secondary;
        }

        $bucket = new StorageBucket(
            $this->getServer(),
            'files-2',
            'file-2:',
            ['directory' => '/secondary/']
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }
}