<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Storage\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Storage\Config\StorageConfig;
use Spiral\Storage\Server\AmazonServer;

class ConfigTest extends TestCase
{
    public function testHasServer()
    {
        $config = new StorageConfig([
            'servers' => [
                'amazon' => [],
                'local'  => []
            ]
        ]);

        $this->assertTrue($config->hasServer('amazon'));
        $this->assertTrue($config->hasServer('local'));
        $this->assertFalse($config->hasServer('rackspace'));
    }

    public function testServer()
    {
        $config = new StorageConfig([
            'servers' => [
                'amazon' => [
                    'class'   => AmazonServer::class,
                    'options' => BaseTest::$OPTS['amazon']
                ]
            ]
        ]);

        $aws = $config->getServer('amazon');
        $c = new Container();
        $this->assertInstanceOf(AmazonServer::class, $aws->resolve($c));
    }

    public function testServerBinded()
    {
        $config = new StorageConfig([
            'servers' => [
                'amazon' => 'aws'
            ]
        ]);

        $aws = $config->getServer('amazon');
        $c = new Container();
        $c->bind('aws', $this);

        $this->assertSame($this, $aws->resolve($c));
    }


    public function testServerWired()
    {
        $config = new StorageConfig([
            'servers' => [
                'amazon' => new Container\Autowire('aws')
            ]
        ]);

        $aws = $config->getServer('amazon');
        $c = new Container();
        $c->bind('aws', $this);

        $this->assertSame($this, $aws->resolve($c));
    }

    /**
     * @expectedException \Spiral\Storage\Exception\ConfigException
     */
    public function testServerMissing()
    {
        $config = new StorageConfig([
            'servers' => []
        ]);

        $config->getServer('amazon');
    }

    /**
     * @expectedException \Spiral\Storage\Exception\ConfigException
     */
    public function testServerInvalid()
    {
        $config = new StorageConfig([
            'servers' => [
                'amazon' => [

                ]
            ]
        ]);

        $config->getServer('amazon');
    }

    public function testBuckets()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon' => ['server' => AmazonServer::class],
            ]
        ]);

        $this->assertTrue($config->hasBucket('amazon'));
        $this->assertFalse($config->hasBucket('other'));
    }

    /**
     * @expectedException \Spiral\Storage\Exception\ConfigException
     */
    public function testBucketOptionsE1()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon' => ['server' => AmazonServer::class],
            ]
        ]);

        $config->getBucket('amazon');
    }

    /**
     * @expectedException \Spiral\Storage\Exception\ConfigException
     */
    public function testBucketOptionsE2()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon' => [
                    'options' => [],
                    'prefix'  => 'aws:'
                ],
            ]
        ]);

        $config->getBucket('amazon');
    }

    /**
     * @expectedException \Spiral\Storage\Exception\ConfigException
     */
    public function testBucketOptionsE3()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon' => [
                    'server'  => AmazonServer::class,
                    'options' => []
                ],
            ]
        ]);

        $config->getBucket('amazon');
    }

    /**
     * @expectedException \Spiral\Storage\Exception\ConfigException
     */
    public function testBucketOptionsE4()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon' => [
                    'server'  => AmazonServer::class,
                    'options' => []
                ],
            ]
        ]);

        $config->getBucket('other');
    }

    /**
     * @expectedException \Spiral\Storage\Exception\ConfigException
     */
    public function testBucketOptionsE5()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon' => [
                    'prefix'  => 'prefix',
                    'options' => []
                ],
            ]
        ]);

        $config->getBucket('amazon');
    }

    public function testBucketOptions()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon' => [
                    'server'  => AmazonServer::class,
                    'prefix'  => 'aws:',
                    'options' => []
                ],
            ]
        ]);

        $aws = $config->getBucket('amazon');
        $this->assertInternalType('array', $aws);
    }


    /**
     * @expectedException \Spiral\Storage\Exception\ResolveException
     */
    public function testResolverE()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon'       => ['prefix' => 'aws:'],
                'ftp'          => ['prefix' => 'ftp:'],
                'amazon-clone' => ['prefix' => 'aws:clone:'],
            ]
        ]);

        $r = $config->getResolver();

        $r->resolveBucket('mixed');
    }

    public function testResolve()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon'       => ['prefix' => 'aws:'],
                'ftp'          => ['prefix' => 'ftp:'],
                'amazon-clone' => ['prefix' => 'aws:clone:'],
                'other'        => []
            ]
        ]);

        $r = $config->getResolver();

        $bucket = $r->resolveBucket('aws:my-object.txt', $name);
        $this->assertSame('amazon', $bucket);
        $this->assertSame('my-object.txt', $name);

        $bucket = $r->resolveBucket('ftp:my-object.txt', $name);
        $this->assertSame('ftp', $bucket);
        $this->assertSame('my-object.txt', $name);

        $bucket = $r->resolveBucket('aws:clone:my-object.txt', $name);
        $this->assertSame('amazon-clone', $bucket);
        $this->assertSame('my-object.txt', $name);

        $r->setBucket('test', 'test:');

        $bucket = $r->resolveBucket('test:my-object.txt', $name);
        $this->assertSame('test', $bucket);
        $this->assertSame('my-object.txt', $name);
    }
}