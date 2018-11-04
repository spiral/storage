<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Tests\FtpServer;

use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Server\FtpServer;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\StorageBucket;

trait ServerTrait
{
    protected static $server;
    protected $bucket;
    protected $secondary;

    protected function getServer(): ServerInterface
    {
        return self::$server ?? self::$server = new FtpServer([
                'host'     => self::$OPTS['ftp']['host'],
                'username' => self::$OPTS['ftp']['username'],
                'password' => self::$OPTS['ftp']['password']
            ]);
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            $this->getServer(),
            'ftp',
            'ftp:',
            [
                'directory' => '/',
                'mode'      => FilesInterface::READONLY
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
            'ftp-2',
            'ftp2:',
            [
                'directory' => 'ftp2/',
                'mode'      => FilesInterface::READONLY
            ]
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }
}