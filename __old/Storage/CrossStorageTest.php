<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage;

use MongoDB\Database;
use MongoDB\Driver\Manager;
use Psr\Http\Message\StreamInterface;
use Spiral\Core\Container;
use Spiral\Storage\Configs\StorageConfig;
use Spiral\Storage\ObjectInterface;
use Spiral\Storage\Server;
use Spiral\Storage\StorageManager;

class CrossStorageTest extends \PHPUnit_Framework_TestCase
{
    protected $skipped = false;

    /**
     * @var StorageManager
     */
    protected $storage;

    static protected $storageCache;

    public function setUp()
    {
        if (empty(env('STORAGE_AMAZON_KEY'))) {
            $this->skipped = true;
        }

        if (empty(env('STORAGE_FTP_USERNAME'))) {
            $this->skipped = true;
        }

        if (empty(env('MONGO_DATABASE'))) {
            $this->skipped = true;
        }

        if (empty(env('STORAGE_RACKSPACE_USERNAME'))) {
            $this->skipped = true;
        }

        if (empty(env('STORAGE_SFTP_USERNAME'))) {
            $this->skipped = true;
        }

        if ($this->skipped) {
            $this->markTestSkipped(
                "CrossStorageTest is available only when all storages are properly configured"
            );
        }

        if (empty(self::$storageCache)) {
            self::$storageCache = $this->createStorage();
        }

        $this->storage = self::$storageCache;
    }

    /**
     * @expectedException \Spiral\Storage\Exception\StorageException
     * @expectedExceptionMessage Unable to locate bucket for a given address 'invalid'
     */
    public function testFailBucketLocation()
    {
        $this->assertFalse($this->storage->open('invalid')->exists());
    }

    public function testBucketAccess()
    {
        $this->assertSame('amazon', $this->storage->locateBucket('amazon:target')->getName());
        $this->assertSame('local', $this->storage->locateBucket('local:target')->getName());
        $this->assertSame('rackspace', $this->storage->locateBucket('rackspace:target')->getName());
        $this->assertSame('sftp', $this->storage->locateBucket('sftp:target')->getName());
        $this->assertSame('ftp', $this->storage->locateBucket('ftp:target')->getName());

        //Little catch
        $this->assertSame('gridFS', $this->storage->locateBucket('grid:target')->getName());
    }

    public function testReplaceInStorages()
    {
        $this->assertFalse($this->storage->open('amazon:target')->exists());

        $content = $this->getStreamSource();

        $object = $this->storage->put('amazon', 'target', $content);

        $this->assertSame('amazon:target', $object->getAddress());
        $this->assertSame('amazon', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //FTP
        $object->replace('ftp');
        $this->assertFalse($this->storage->open('amazon:target')->exists());

        $this->assertSame('ftp:target', $object->getAddress());
        $this->assertSame('ftp', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //SFTP
        $object->replace('sftp');
        $this->assertFalse($this->storage->open('ftp:target')->exists());

        $this->assertSame('sftp:target', $object->getAddress());
        $this->assertSame('sftp', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //GridFS
        $object->replace('gridFS');
        $this->assertFalse($this->storage->open('sftp:target')->exists());

        $this->assertSame('grid:target', $object->getAddress());
        $this->assertSame('gridFS', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //Local
        $object->replace('local');
        $this->assertFalse($this->storage->open('grid:target')->exists());

        $this->assertSame('local:target', $object->getAddress());
        $this->assertSame('local', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //Local
        $object->replace('rackspace');
        $this->assertFalse($this->storage->open('local:target')->exists());

        $this->assertSame('rackspace:target', $object->getAddress());
        $this->assertSame('rackspace', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        $object->replace('amazon');
        $this->assertFalse($this->storage->open('rackspace:target')->exists());

        $this->assertSame('amazon:target', $object->getAddress());
        $this->assertSame('amazon', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        $object->delete();

        $this->assertFalse($object->exists());
    }

    public function testCopyInStorages()
    {
        $object = $this->storage->open('amazon:target');
        $this->assertFalse($object->exists());

        $content = $this->getStreamSource();

        $object = $this->storage->put('amazon', 'target', $content);

        $ftpObject = $object->copy('ftp');
        $sftpObject = $object->copy('sftp');
        $gridObject = $object->copy('gridFS');
        $localObject = $object->copy('local');
        $rackspaceObject = $object->copy('rackspace');

        $this->assertSame('amazon:target', $object->getAddress());
        $this->assertSame('amazon', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        $this->assertSame('ftp:target', $ftpObject->getAddress());
        $this->assertSame('ftp', $ftpObject->getBucket()->getName());
        $this->assertContent($content, $ftpObject);

        $this->assertSame('sftp:target', $sftpObject->getAddress());
        $this->assertSame('sftp', $sftpObject->getBucket()->getName());
        $this->assertContent($content, $sftpObject);

        $this->assertSame('grid:target', $gridObject->getAddress());
        $this->assertSame('gridFS', $gridObject->getBucket()->getName());
        $this->assertContent($content, $gridObject);

        $this->assertSame('local:target', $localObject->getAddress());
        $this->assertSame('local', $localObject->getBucket()->getName());
        $this->assertContent($content, $localObject);

        $this->assertSame('rackspace:target', $rackspaceObject->getAddress());
        $this->assertSame('rackspace', $rackspaceObject->getBucket()->getName());
        $this->assertContent($content, $rackspaceObject);

        $object->delete();
        $ftpObject->delete();
        $gridObject->delete();
        $localObject->delete();
        $sftpObject->delete();
        $rackspaceObject->delete();
    }

    protected function assertContent(StreamInterface $stream, ObjectInterface $object)
    {
        $stream->rewind();
        $this->assertSame($stream->getContents(), $object->getStream()->getContents());
    }

    protected function createStorage(): StorageManager
    {
        $config = new StorageConfig([
            'servers' => [
                'local'     => [
                    'class'   => Server\LocalServer::class,
                    'options' => [
                        'home' => __DIR__ . '/Servers/LocalServer/fixtures/'
                    ]
                ],
                'amazon'    => [
                    'class'   => Server\AmazonServer::class,
                    'options' => [
                        'accessKey' => env('STORAGE_AMAZON_KEY'),
                        'secretKey' => env('STORAGE_AMAZON_SECRET'),
                    ]
                ],
                'rackspace' => [
                    'class'   => Server\RackspaceServer::class,
                    'options' => [
                        'username' => env('STORAGE_RACKSPACE_USERNAME'),
                        'apiKey'   => env('STORAGE_RACKSPACE_API_KEY')
                    ]
                ],
                'ftp'       => [
                    'class'   => Server\FtpServer::class,
                    'options' => [
                        'host'     => env('STORAGE_FTP_HOST'),
                        'login'    => env('STORAGE_FTP_USERNAME'),
                        'password' => env('STORAGE_FTP_PASSWORD')
                    ]
                ],
                'sftp'      => [
                    'class'   => Server\SftpServer::class,
                    'options' => [
                        'host'       => env('STORAGE_SFTP_HOST'),
                        'home'       => env('STORAGE_SFTP_HOME'),
                        'authMethod' => 'password',
                        'username'   => env('STORAGE_SFTP_USERNAME'),
                        'password'   => env('STORAGE_SFTP_PASSWORD'),
                    ]
                ],
                'gridFS'    => [
                    'class'   => Server\GridFSServer::class,
                    'options' => []
                ],
            ],

            'buckets' => [
                'local'     => [
                    'server'  => 'local',
                    'prefix'  => 'local:',
                    'options' => ['directory' => '/']
                ],
                'amazon'    => [
                    'server'  => 'amazon',
                    'prefix'  => 'amazon:',
                    'options' => [
                        'public' => false,
                        'bucket' => env('STORAGE_AMAZON_BUCKET')
                    ]
                ],
                'rackspace' => [
                    'server'  => 'rackspace',
                    'prefix'  => 'rackspace:',
                    'options' => [
                        'container' => env('STORAGE_RACKSPACE_CONTAINER'),
                        'region'    => env('STORAGE_RACKSPACE_REGION')
                    ]
                ],
                'ftp'       => [
                    'server'  => 'ftp',
                    'prefix'  => 'ftp:',
                    'options' => [
                        'directory' => env('STORAGE_FTP_DIRECTORY'),
                        'mode'      => \Spiral\Files\FilesInterface::RUNTIME
                    ]
                ],
                'sftp'      => [
                    'server'  => 'sftp',
                    'prefix'  => 'sftp:',
                    'options' => [
                        'directory' => 'uploads',
                        'mode'      => \Spiral\Files\FilesInterface::RUNTIME
                    ]
                ],
                'gridFS'    => [
                    'server'  => 'gridFS',
                    'prefix'  => 'grid:',
                    'options' => ['bucket' => 'files']
                ],
            ]
        ]);

        $container = new Container();
        $container->bind(
            Database::class,
            new Database(new Manager(env('MONGO_CONNECTION')), env('MONGO_DATABASE'))
        );

        return new StorageManager($config, $container);
    }

    protected function getStreamSource(): StreamInterface
    {
        $content = random_bytes(mt_rand(100, 100000));

        return \GuzzleHttp\Psr7\stream_for($content);
    }
}