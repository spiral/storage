<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Storage\Tests\GridFSServer;

use MongoDB\Database;
use MongoDB\Driver\Manager;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Server\GridFSServer;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\StorageBucket;

trait ServerTrait
{
    protected $bucket;
    protected $secondary;
    protected $server;

    protected function getServer(): ServerInterface
    {
        return $this->server ?? $this->server = new GridFSServer(
                new Database(
                    new Manager(self::$OPTS['mongodb']['conn']),
                    self::$OPTS['mongodb']['database']
                )
            );
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            $this->getServer(),
            'mongo',
            'mongo:',
            ['bucket' => 'grid-fs']
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
            'mongo-2',
            'mongo-2:',
            ['bucket' => 'grid-fs-2']
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }
}