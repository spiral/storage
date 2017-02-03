<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Tests\Storage;

use Psr\Http\Message\StreamInterface;

abstract class OperationsTest extends BaseTest
{
    public function tearDown()
    {
        if ($this->skipped) {
            return;
        }

        $this->getBucket()->exists('target') && $this->getBucket()->delete('target');
        $this->getBucket()->exists('targetB') && $this->getBucket()->delete('targetB');
        $this->getBucket()->exists('targetDir/targetName') && $this->getBucket()->delete('targetDir/targetName');
    }

    /**
     * @expectedException \Spiral\Storage\Exceptions\BucketException
     * @expectedExceptionMessage Source must be a valid resource, stream or filename, invalid value
     *                           given
     */
    public function testPutString()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = random_bytes(mt_rand(100, 100000));
        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));
    }

    public function testPutEmptyString()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $bucket->put('target', '');
        $this->assertTrue($bucket->exists('target'));
        $this->assertSame(0, $bucket->size('target'));
    }

    public function testPutStream()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();
        $bucket->put('target', $content);

        $this->assertTrue($bucket->exists('target'));
    }

    public function testPutStreamLongName()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();
        $bucket->put('targetDir/targetName', $content);

        $this->assertTrue($bucket->exists('targetDir/targetName'));
    }

    public function testPutFilename()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = __FILE__;
        $bucket->put('target', $content);

        $this->assertTrue($bucket->exists('target'));
    }

    public function testPutResource()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = fopen(__FILE__, 'rb');
        $bucket->put('target', $content);

        $this->assertTrue($bucket->exists('target'));
    }

    public function testAddress()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();
        $address = $bucket->put('target', $content);

        $this->assertNotNull($address);
        $this->assertSame($bucket->getPrefix() . 'target', $address);
    }

    public function testStreamIntegrity()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();
        $bucket->put('target', $content);

        $content->rewind();

        $stream = $bucket->allocateStream('target');
        $this->assertSame($content->getContents(), $stream->getContents());
    }

    public function testResourceIntegrity()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = fopen(__FILE__, 'rb');
        $bucket->put('target', $content);

        $stream = $bucket->allocateStream('target');
        $this->assertSame(file_get_contents(__FILE__), $stream->getContents());
    }

    public function testFilenameIntegrity()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = __FILE__;
        $bucket->put('target', $content);

        $stream = $bucket->allocateStream('target');
        $this->assertSame(file_get_contents(__FILE__), $stream->getContents());
    }

    public function testSize()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();
        $bucket->put('target', $content);

        $this->assertTrue($bucket->exists('target'));

        $this->assertSame($content->getSize(), $bucket->size('target'));
    }

    public function testSizeNull()
    {
        $bucket = $this->getBucket();
        $this->assertSame(null, $bucket->size('target'));
    }

    public function testLocalFilename()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $localFilename = $bucket->allocateFilename('target');

        $this->assertNotEmpty($localFilename);
        $this->assertTrue(file_exists($localFilename));

        //Written!
        $content->rewind();

        $this->assertSame($content->getContents(), file_get_contents($localFilename));
    }

    public function testLocalStream()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $stream = $bucket->allocateStream('target');
        $this->assertInstanceOf(StreamInterface::class, $stream);

        //Written!
        $content->rewind();

        $this->assertSame($content->getSize(), $stream->getSize());
        $this->assertSame($content->getContents(), $stream->getContents());
    }

    public function testRename()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

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

    public function testReplaceContent()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        //Written!
        $content->rewind();

        $stream = $bucket->allocateStream('target');

        $this->assertSame($content->getSize(), $stream->getSize());
        $this->assertSame($content->getContents(), $stream->getContents());

        $newContent = $this->getStreamSource();

        $bucket->put('target', $newContent);
        $this->assertTrue($bucket->exists('target'));

        //Written!
        $newContent->rewind();

        $stream = $bucket->allocateStream('target');

        $this->assertSame($newContent->getSize(), $stream->getSize());
        $this->assertSame($newContent->getContents(), $stream->getContents());
    }

    public function testRenameLongName()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $newAddress = $bucket->rename('target', 'targetDir/targetName');

        $this->assertNotNull($newAddress);
        $this->assertSame($bucket->getPrefix() . 'targetDir/targetName', $newAddress);

        $this->assertFalse($bucket->exists('target'));
        $this->assertTrue($bucket->exists('targetDir/targetName'));

        $stream = $bucket->allocateStream('targetDir/targetName');
        $this->assertInstanceOf(StreamInterface::class, $stream);

        //Written!
        $content->rewind();

        $this->assertSame($content->getSize(), $stream->getSize());
        $this->assertSame($content->getContents(), $stream->getContents());
    }

    public function testDelete()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = $this->getStreamSource();

        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $bucket->delete('target');
        $this->assertFalse($bucket->exists('target'));
    }

    /**
     * @expectedException \Spiral\Storage\Exceptions\BucketException
     */
    public function testDeleteUndefined()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));
        $bucket->delete('target');
    }
}