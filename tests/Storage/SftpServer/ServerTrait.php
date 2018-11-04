<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Tests\SftpServer;

use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Server\SftpServer;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\StorageBucket;

trait ServerTrait
{
    protected static $server;
    protected $bucket;
    protected $secondary;

    protected function getServer(): ServerInterface
    {
        return self::$server ?? self::$server = new SftpServer([
                'host'     => self::$OPTS['sftp']['host'],
                'port'     => 2222,
                'username' => self::$OPTS['sftp']['username'],
                'password' => self::$OPTS['sftp']['password'],
                'home'     => '/upload',
            ]);
    }

    protected function getBucket(): BucketInterface
    {
        if (!empty($this->bucket)) {
            return $this->bucket;
        }

        $bucket = new StorageBucket(
            $this->getServer(),
            'sftp',
            'sftp:',
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
            'sftp-2',
            'sftp2:',
            [
                'directory' => '/sftp2/',
                'mode'      => FilesInterface::READONLY
            ]
        );

        $bucket->setLogger($this->makeLogger());

        return $this->secondary = $bucket;
    }
}