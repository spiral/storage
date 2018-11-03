<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\CachedRackspaceServer;

use Spiral\Storage\BucketInterface;
use Spiral\Storage\Entity\StorageBucket;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\Server\RackspaceServer;

trait ServerTrait
{
    protected $bucket;
    protected $secondary;
    protected $server;

    public function setUp()
    {
        if (empty(env('STORAGE_RACKSPACE_USERNAME'))) {
            $this->skipped = true;
            $this->markTestSkipped('Rackspace credentials are not set');
        }
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            'rackspace',
            env('STORAGE_RACKSPACE_PREFIX'),
            [
                'container' => env('STORAGE_RACKSPACE_CONTAINER'),
                'region'    => env('STORAGE_RACKSPACE_REGION')
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
            'rackspace-2',
            env('STORAGE_RACKSPACE_PREFIX_2'),
            [
                'container' => env('STORAGE_RACKSPACE_CONTAINER_2'),
                'region'    => env('STORAGE_RACKSPACE_REGION_2')
            ],
            $this->getServer()
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }

    protected function getServer(): ServerInterface
    {
        return $this->server ?? $this->server = new RackspaceServer([
                'username' => env('STORAGE_RACKSPACE_USERNAME'),
                'apiKey'   => env('STORAGE_RACKSPACE_API_KEY')
            ], AuthCache::getCache());
    }
}