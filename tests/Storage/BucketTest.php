<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Tests;

use Spiral\Storage\StorageObject;

abstract class BucketTest extends BaseTest
{
    public function tearDown()
    {
        parent::tearDown();

        if ($this->getBucket()->exists('target')) {
            $this->getBucket()->delete('target');
        }

        if ($this->getBucket()->exists('targetB')) {
            $this->getBucket()->delete('targetB');
        }

        if ($this->getBucket()->exists('targetDir/targetName')) {
            $this->getBucket()->delete('targetDir/targetName');
        }
    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     * @expectedExceptionMessage Source must be a valid resource, stream or filename, invalid value
//     *                           given
//     */
//    public function testPutString()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = random_bytes(mt_rand(100, 100000));
//        $bucket->put('target', $content);
//    }
//
//    public function testPutEmptyString()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $bucket->put('target', '');
//        $this->assertTrue($bucket->exists('target'));
//        $this->assertSame(0, $bucket->size('target'));
//    }
//
//    public function testPutStream()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//        $bucket->put('target', $content);
//
//        $this->assertTrue($bucket->exists('target'));
//    }
//
//    public function testPutStreamLongName()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//        $bucket->put('targetDir/targetName', $content);
//
//        $this->assertTrue($bucket->exists('targetDir/targetName'));
//    }
//
//    public function testPutFilename()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = __FILE__;
//        $bucket->put('target', $content);
//
//        $this->assertTrue($bucket->exists('target'));
//    }

    public function testPutObject()
    {
        $bucket = $this->getBucket();

        $this->assertFalse($bucket->exists('target'));

        $content = __FILE__;
        $bucket->put('target', $content);
        $this->assertTrue($bucket->exists('target'));

        $object = new StorageObject($bucket, 'target');

        $bucket->put('target4', $object);
        $bucket->delete('target');

        $this->assertTrue($bucket->exists('target4'));
        $bucket->delete('target4');
    }
//
//    public function testPutResource()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = fopen(__FILE__, 'rb');
//        $bucket->put('target', $content);
//
//        $this->assertTrue($bucket->exists('target'));
//    }
//
//    public function testAddress()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//        $address = $bucket->put('target', $content);
//
//        $this->assertNotNull($address);
//        $this->assertSame($bucket->getPrefix() . 'target', $address);
//    }
//
//    public function testStreamIntegrity()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//        $bucket->put('target', $content);
//
//        $content->rewind();
//
//        $stream = $bucket->allocateStream('target');
//        $this->assertSame($content->getContents(), $stream->getContents());
//    }
//
//    public function testResourceIntegrity()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = fopen(__FILE__, 'rb');
//        $bucket->put('target', $content);
//
//        $stream = $bucket->allocateStream('target');
//        $this->assertSame(file_get_contents(__FILE__), $stream->getContents());
//    }
//
//    public function testFilenameIntegrity()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = __FILE__;
//        $bucket->put('target', $content);
//
//        $stream = $bucket->allocateStream('target');
//        $this->assertSame(file_get_contents(__FILE__), $stream->getContents());
//    }
//
//    public function testSize()
//    {
//        $bucket = $this->getBucket();
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//        $bucket->put('target', $content);
//
//        $this->assertTrue($bucket->exists('target'));
//
//        $this->assertSame($content->getSize(), $bucket->size('target'));
//    }
//
//    public function testSizeNull()
//    {
//        $bucket = $this->getBucket();
//        $this->assertSame(null, $bucket->size('target'));
//    }
//
//    public function testLocalFilename()
//    {
//        $bucket = $this->getBucket();
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//
//        $bucket->put('target', $content);
//        $this->assertTrue($bucket->exists('target'));
//
//        $localFilename = $bucket->allocateFilename('target');
//
//        $this->assertNotEmpty($localFilename);
//        $this->assertTrue(file_exists($localFilename));
//
//        //Written!
//        $content->rewind();
//
//        $this->assertSame($content->getContents(), file_get_contents($localFilename));
//    }
//
//    public function testLocalStream()
//    {
//        $bucket = $this->getBucket();
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//
//        $bucket->put('target', $content);
//        $this->assertTrue($bucket->exists('target'));
//
//        $stream = $bucket->allocateStream('target');
//        $this->assertInstanceOf(StreamInterface::class, $stream);
//
//        //Written!
//        $content->rewind();
//
//        $this->assertSame($content->getSize(), $stream->getSize());
//        $this->assertSame($content->getContents(), $stream->getContents());
//    }
//
//    public function testRename()
//    {
//        $bucket = $this->getBucket();
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//
//        $bucket->put('target', $content);
//        $this->assertTrue($bucket->exists('target'));
//
//        $newAddress = $bucket->rename('target', 'targetB');
//
//        $this->assertNotNull($newAddress);
//        $this->assertSame($bucket->getPrefix() . 'targetB', $newAddress);
//
//        $this->assertFalse($bucket->exists('target'));
//        $this->assertTrue($bucket->exists('targetB'));
//
//        $stream = $bucket->allocateStream('targetB');
//        $this->assertInstanceOf(StreamInterface::class, $stream);
//
//        //Written!
//        $content->rewind();
//
//        $this->assertSame($content->getSize(), $stream->getSize());
//        $this->assertSame($content->getContents(), $stream->getContents());
//    }
//
//    public function testReplaceContent()
//    {
//        $bucket = $this->getBucket();
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//
//        $bucket->put('target', $content);
//        $this->assertTrue($bucket->exists('target'));
//
//        //Written!
//        $content->rewind();
//
//        $stream = $bucket->allocateStream('target');
//
//        $this->assertSame($content->getSize(), $stream->getSize());
//        $this->assertSame($content->getContents(), $stream->getContents());
//
//        $newContent = $this->generateStream();
//
//        $bucket->put('target', $newContent);
//        $this->assertTrue($bucket->exists('target'));
//
//        //Written!
//        $newContent->rewind();
//
//        $stream = $bucket->allocateStream('target');
//
//        $this->assertSame($newContent->getSize(), $stream->getSize());
//        $this->assertSame($newContent->getContents(), $stream->getContents());
//    }
//
//    public function testRenameLongName()
//    {
//        $bucket = $this->getBucket();
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//
//        $bucket->put('target', $content);
//        $this->assertTrue($bucket->exists('target'));
//
//        $newAddress = $bucket->rename('target', 'targetDir/targetName');
//
//        $this->assertNotNull($newAddress);
//        $this->assertSame($bucket->getPrefix() . 'targetDir/targetName', $newAddress);
//
//        $this->assertFalse($bucket->exists('target'));
//        $this->assertTrue($bucket->exists('targetDir/targetName'));
//
//        $stream = $bucket->allocateStream('targetDir/targetName');
//        $this->assertInstanceOf(StreamInterface::class, $stream);
//
//        //Written!
//        $content->rewind();
//
//        $this->assertSame($content->getSize(), $stream->getSize());
//        $this->assertSame($content->getContents(), $stream->getContents());
//    }
//
//    public function testInternalCopy()
//    {
//        $bucket = $this->getBucket();
//        $bucketB = $this->getSecondaryBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//        $this->assertFalse($bucketB->exists('target'));
//
//        $content = $this->generateStream();
//
//        $bucket->put('target', $content);
//        $bucket->copy($bucketB, 'target');
//
//        $this->assertTrue($bucket->exists('target'));
//        $this->assertTrue($bucketB->exists('target'));
//
//
//        $bucket->delete('target');
//        $bucketB->delete('target');
//
//        $this->assertFalse($bucket->exists('target'));
//        $this->assertFalse($bucketB->exists('target'));
//    }
//
//    public function testInternalReplace()
//    {
//        $bucket = $this->getBucket();
//        $bucketB = $this->getSecondaryBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//        $this->assertFalse($bucketB->exists('target'));
//
//        $content = $this->generateStream();
//
//        $bucket->put('target', $content);
//        $bucket->replace($bucketB, 'target');
//
//        $this->assertFalse($bucket->exists('target'));
//        $this->assertTrue($bucketB->exists('target'));
//
//        $bucketB->delete('target');
//        $this->assertFalse($bucketB->exists('target'));
//    }
//
//    public function testDelete()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//
//        $content = $this->generateStream();
//
//        $bucket->put('target', $content);
//        $this->assertTrue($bucket->exists('target'));
//
//        $bucket->delete('target');
//        $this->assertFalse($bucket->exists('target'));
//    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     */
//    public function testDeleteUndefined()
//    {
//        $bucket = $this->getBucket();
//
//        $this->assertFalse($bucket->exists('target'));
//        $bucket->delete('target');
//    }
//
//    public function testWithOptions()
//    {
//        $bucket = new StorageBucket(
//            m::mock(ServerInterface::class),
//            'bucket',
//            'bucket:',
//            ['name' => 'value']
//        );
//
//        $this->assertSame('value', $bucket->getOption('name'));
//
//        $bucket1 = $bucket->withOption('name', 'value1');
//        $this->assertSame('value', $bucket->getOption('name'));
//        $this->assertSame('value1', $bucket1->getOption('name'));
//    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     */
//    public function testBypassEx1()
//    {
//        $s = m::mock(ServerInterface::class);
//        $s->expects('exists')->andThrows(ServerException::class);
//
//        $b = new StorageBucket($s, '', '', []);
//        $b->exists('name');
//    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     */
//    public function testBypassEx2()
//    {
//        $s = m::mock(ServerInterface::class);
//        $s->expects('size')->andThrows(ServerException::class);
//
//        $b = new StorageBucket($s, '', '', []);
//        $b->size('name');
//    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     */
//    public function testBypassEx3()
//    {
//        $s = m::mock(ServerInterface::class);
//        $s->expects('allocateFilename')->andThrows(ServerException::class);
//
//        $b = new StorageBucket($s, '', '', []);
//        $b->allocateFilename('name');
//    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     */
//    public function testBypassEx4()
//    {
//        $s = m::mock(ServerInterface::class);
//        $s->expects('allocateStream')->andThrows(ServerException::class);
//
//        $b = new StorageBucket($s, '', '', []);
//        $b->allocateStream('name');
//    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     */
//    public function testBypassEx5()
//    {
//        $s = m::mock(ServerInterface::class);
//        $s->expects('rename')->andThrows(ServerException::class);
//
//        $b = new StorageBucket($s, '', '', []);
//        $b->rename('name', 'name');
//        $b->rename('name', 'newName');
//    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     */
//    public function testBypassEx6()
//    {
//        $s = m::mock(ServerInterface::class);
//        $s->expects('copy')->andThrows(ServerException::class);
//
//        $b = new StorageBucket($s, '', '', []);
//        $b->copy($b, 'name');
//        $b->copy(clone $b, 'name2');
//    }
//
//    /**
//     * @expectedException \Spiral\Storage\Exception\BucketException
//     */
//    public function testBypassEx7()
//    {
//        $s = m::mock(ServerInterface::class);
//        $s->expects('replace')->andThrows(ServerException::class);
//
//        $b = new StorageBucket($s, '', '', []);
//        $b->replace($b, 'name');
//        $b->replace(clone $b, 'name2');
//    }
}