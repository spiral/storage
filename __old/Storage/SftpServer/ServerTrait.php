<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\SftpServer;

use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Entity\StorageBucket;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\Server\SftpServer;

trait ServerTrait
{
    protected $bucket;
    protected $secondary;
    protected $server;

    public function setUp()
    {
        if (empty(env('STORAGE_SFTP_USERNAME'))) {
            $this->skipped = true;
            $this->markTestSkipped('SFTP credentials are not set');
        }
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            'sftp',
            env('STORAGE_SFTP_PREFIX'),
            [
                'directory' => env('STORAGE_SFTP_DIRECTORY'),
                'mode'      => FilesInterface::READONLY
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
            'sftp-2',
            env('STORAGE_SFTP_PREFIX_2'),
            [
                'directory' => env('STORAGE_SFTP_DIRECTORY_2'),
                'mode'      => FilesInterface::READONLY
            ],
            $this->getServer()
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }

    protected function getServer(): ServerInterface
    {
        return $this->server ?? $this->server = new SftpServer([
                'host'     => env('STORAGE_SFTP_HOST'),
                'username' => env('STORAGE_SFTP_USERNAME'),
                'password' => env('STORAGE_SFTP_PASSWORD'),
                'home'     => env('STORAGE_SFTP_HOME'),
            ]);
    }
}