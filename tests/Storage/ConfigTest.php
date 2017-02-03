<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage;

use Spiral\Storage\Configs\StorageConfig;
use Spiral\Storage\Servers\AmazonServer;
use Spiral\Storage\Servers\LocalServer;

class ConfigTest extends \PHPUnit_Framework_TestCase
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

    public function testServerClass()
    {
        $config = new StorageConfig([
            'servers' => [
                'amazon' => [
                    'class'  => AmazonServer::class,
                    'option' => 'option'
                ]
            ]
        ]);

        $this->assertSame([
            'option' => 'option'
        ], $config->serverOptions('amazon'));
    }

    public function testServerOptions()
    {
        $config = new StorageConfig([
            'servers' => [
                'amazon' => [
                    'class' => AmazonServer::class
                ],
                'local'  => [
                    'class' => LocalServer::class
                ]
            ]
        ]);

        $this->assertSame(AmazonServer::class, $config->serverClass('amazon'));
        $this->assertSame(LocalServer::class, $config->serverClass('local'));
    }

    public function testBuckets()
    {
        $config = new StorageConfig([
            'buckets' => [
                'amazon' => [
                    'server' => AmazonServer::class
                ],
                'local'  => [
                    'server' => LocalServer::class
                ]
            ]
        ]);

        $this->assertSame([
            'amazon' => [
                'server' => AmazonServer::class
            ],
            'local'  => [
                'server' => LocalServer::class
            ]
        ], $config->getBuckets());
    }
}