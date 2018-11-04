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
use Spiral\Storage\Server\LocalServer;

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
                'local'  => ['server' => LocalServer::class]
            ]
        ]);

        $this->assertSame([
            'amazon' => ['server' => AmazonServer::class],
            'local'  => ['server' => LocalServer::class]
        ], $config->getBuckets());
    }
}