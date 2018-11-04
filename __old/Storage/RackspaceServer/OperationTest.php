<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\RackspaceServer;

use Psr\Http\Message\StreamInterface;

class OperationTest extends \Spiral\Tests\Storage\OperationTest
{
    use ServerTrait;

    public function testPutEmptyStringBadToken()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $this->breakToken();
        $bucket->put('target', '');
        $this->assertTrue($bucket->exists('target'));
        $this->assertSame(0, $bucket->size('target'));
    }

    public function testSizeBadToken()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();
        $bucket->put('target', $content);

        $this->breakToken();
        $this->assertTrue($bucket->exists('target'));

        $this->breakToken();
        $this->assertSame($content->getSize(), $bucket->size('target'));
    }

    public function testLocalFilenameBadToken()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $this->breakToken();
        $localFilename = $bucket->allocateFilename('target');

        $this->assertNotEmpty($localFilename);
        $this->assertTrue(file_exists($localFilename));

        //Written!
        $content->rewind();

        $this->assertSame($content->getContents(), file_get_contents($localFilename));
    }

    public function testLocalStreamBadToken()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $this->breakToken();
        $stream = $bucket->allocateStream('target');
        $this->assertInstanceOf(StreamInterface::class, $stream);

        //Written!
        $content->rewind();

        $this->assertSame($content->getSize(), $stream->getSize());
        $this->assertSame($content->getContents(), $stream->getContents());
    }

    public function testRenameBadToken()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $this->breakToken();
        $newAddress = $bucket->rename('target', 'targetB');

        $this->assertNotNull($newAddress);
        $this->assertSame($bucket->getPrefix() . 'targetB', $newAddress);

        $this->assertFalse($bucket->exists('target'));
        $this->assertTrue($bucket->exists('targetB'));

        $stream = $bucket->allocateStream('targetB');
        $this->assertInstanceOf(StreamInterface::class, $stream);

        //Written!
        $content->rewind();

        $this->assertSame($content->getSize(), $stream->getSize());
        $this->assertSame($content->getContents(), $stream->getContents());
    }

    public function testDeleteBadToken()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $this->breakToken();
        $bucket->delete('target');
        $this->assertFalse($bucket->exists('target'));
    }

    private function breakToken()
    {
        $reflection = new \ReflectionClass(get_class($this->server));
        $property = $reflection->getProperty('authToken');
        $property->setAccessible(true);
        $property->setValue($this->server, 'bad');
    }
}