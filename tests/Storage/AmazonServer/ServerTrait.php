<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Tests\AmazonServer;


use Spiral\Storage\BucketInterface;
use Spiral\Storage\Server\AmazonServer;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\StorageBucket;

trait ServerTrait
{
    protected static $server;
    protected $bucket;
    protected $secondary;

    protected function getServer(): ServerInterface
    {
        return self::$server ?? self::$server = new AmazonServer([
                'server' => self::$OPTS['amazon']['server'],
                'key'    => self::$OPTS['amazon']['key'],
                'secret' => self::$OPTS['amazon']['secret']
            ]);
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            $this->getServer(),
            'amazon',
            'aws:',
            [
                'bucket' => 'aws1',
                'public' => false
            ]
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
            'amazon-2',
            'aws2:',
            [
                'bucket' => 'aws2',
                'public' => false
            ]
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }
}