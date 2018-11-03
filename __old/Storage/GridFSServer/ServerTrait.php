<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\GridFSServer;

use MongoDB\Database;
use MongoDB\Driver\Manager;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Entity\StorageBucket;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\Server\GridFSServer;

trait ServerTrait
{
    protected $bucket;
    protected $secondary;
    protected $server;

    public function setUp()
    {
        if (empty(env('MONGO_DATABASE'))) {
            $this->skipped = true;
            $this->markTestSkipped('Mongo credentials are not set');
        }
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            'mongo',
            'mongo:',
            ['bucket' => 'grid-fs'],
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
            'mongo-2',
            'mongo-2:',
            ['bucket' => 'grid-fs-2'],
            $this->getServer()
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }

    protected function getServer(): ServerInterface
    {
        return $this->server ?? $this->server = new GridFSServer(
                new Database(new Manager(env('MONGO_CONNECTION')), env('MONGO_DATABASE'))
            );
    }
}