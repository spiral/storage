<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Storage\Config\StorageConfig;
use Spiral\Storage\Server;
use Spiral\Storage\StorageInterface;
use Spiral\Storage\StorageManager;

class StorageTest extends TestCase
{
    private static $storage;

    public function getStorage(): StorageInterface
    {
        if (!empty(self::$storage)) {
            return self::$storage;
        }

        $config = new StorageConfig([
            'servers' => [
                'local'  => [
                    'class'   => Server\LocalServer::class,
                    'options' => [
                        'home' => BaseTest::$OPTS['home']
                    ]
                ],
                'amazon' => [
                    'class'   => Server\AmazonServer::class,
                    'options' => [
                        'server' => BaseTest::$OPTS['amazon']['server'],
                        'key'    => BaseTest::$OPTS['amazon']['key'],
                        'secret' => BaseTest::$OPTS['amazon']['secret']
                    ]
                ],
                'ftp'    => [
                    'class'   => Server\FtpServer::class,
                    'options' => [
                        'host'     => BaseTest::$OPTS['ftp']['host'],
                        'username' => BaseTest::$OPTS['ftp']['username'],
                        'password' => BaseTest::$OPTS['ftp']['password']
                    ]
                ],
                'sftp'   => [
                    'class'   => Server\SftpServer::class,
                    'options' => [
                        'host'     => BaseTest::$OPTS['sftp']['host'],
                        'port'     => BaseTest::$OPTS['sftp']['port'],
                        'username' => BaseTest::$OPTS['sftp']['username'],
                        'password' => BaseTest::$OPTS['sftp']['password'],
                        'home'     => BaseTest::$OPTS['sftp']['home'],
                    ]
                ],
                'gridFS' => [
                    'class'   => Server\GridFSServer::class,
                    'options' => [
                        'connection' => BaseTest::$OPTS['mongodb']['connection'],
                        'database'   => BaseTest::$OPTS['mongodb']['database']
                    ]
                ],
            ],

            'buckets' => [
                'files'  => [
                    'server'  => 'local',
                    'prefix'  => 'file:',
                    'options' => ['directory' => '/']
                ],
                'amazon' => [
                    'server'  => 'amazon',
                    'prefix'  => 'aws:',
                    'options' => [
                        'public' => false,
                        'bucket' => 'aws1'
                    ]
                ],
                'ftp'    => [
                    'server'  => 'ftp',
                    'prefix'  => 'ftp:',
                    'options' => [
                        'directory' => '/',
                        'mode'      => \Spiral\Files\FilesInterface::RUNTIME
                    ]
                ],
                'sftp'   => [
                    'server'  => 'sftp',
                    'prefix'  => 'sftp:',
                    'options' => [
                        'directory' => '/',
                        'mode'      => \Spiral\Files\FilesInterface::RUNTIME
                    ]
                ],
                'mongo'  => [
                    'server'  => 'gridFS',
                    'prefix'  => 'mongo:',
                    'options' => ['bucket' => 'files']
                ],
            ]
        ]);

        return self::$storage = new StorageManager($config, new Container());
    }
}