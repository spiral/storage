<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\FtpServer;

use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Entities\StorageBucket;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\Servers\FtpServer;

trait ServerTrait
{
    protected $bucket;
    protected $secondary;
    protected $server;

    public function setUp()
    {
        if (empty(env('STORAGE_FTP_USERNAME'))) {
            $this->skipped = true;
            $this->markTestSkipped('FTP credentials are not set');
        }
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            'ftp',
            env('STORAGE_FTP_PREFIX'),
            [
                'directory' => env('STORAGE_FTP_DIRECTORY'),
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
            'ftp-2',
            env('STORAGE_FTP_PREFIX_2'),
            [
                'directory' => env('STORAGE_FTP_DIRECTORY_2'),
                'mode'      => FilesInterface::READONLY
            ],
            $this->getServer()
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }

    protected function getServer(): ServerInterface
    {
        return $this->server ?? $this->server = new FtpServer([
                'host'     => env('STORAGE_FTP_HOST'),
                'login'    => env('STORAGE_FTP_USERNAME'),
                'password' => env('STORAGE_FTP_PASSWORD')
            ]);
    }
}