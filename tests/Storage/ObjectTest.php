<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Tests;

use Spiral\Storage\ObjectInterface;

abstract class ObjectTest extends BaseTest
{
    public function tearDown()
    {
        if ($this->getBucket()->exists('target')) {
            $this->getBucket()->delete('target');
        }

        if ($this->getSecondaryBucket()->exists('target')) {
            $this->getSecondaryBucket()->delete('target');
        }

        if ($this->getBucket()->exists('targetB')) {
            $this->getBucket()->delete('targetB');
        }

        if ($this->getBucket()->exists('targetDir/targetName')) {
            $this->getBucket()->delete('targetDir/targetName');
        }
    }

    public function testObject()
    {
        $this->assertInstanceOf(
            ObjectInterface::class,
            $this->getStorage()->open($this->makeAddress('target'))
        );
    }

    public function testObjectExists()
    {
        $object = $this->getStorage()->open($this->makeAddress('target'));

        $this->assertFalse($object->exists());
        $this->assertNull($object->getSize());

        $this->assertSame($this->getBucket()->getName(), $object->getBucket()->getName());
    }

    /**
     * @expectedException \Spiral\Storage\Exception\BucketException
     * @expectedExceptionMessage Source must be a valid resource, stream or filename, invalid value
     *                           given
     */
    public function testObjectPutString()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        //By Name
        $this->getStorage()->put($this->getBucket()->getName(), 'target', 'STRING');
    }

    public function testPutStream()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            $this->generateStream()
        );

        $this->assertTrue($object->exists());
    }

    public function testPutStreamLongName()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('targetDir/targetName'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'targetDir/targetName',
            $this->generateStream()
        );

        $this->assertTrue($object->exists());
    }

    public function testPutFilename()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $this->assertTrue($object->exists());
    }

    public function testPutResource()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            fopen(__FILE__, 'rb')
        );

        $this->assertTrue($object->exists());
    }

    public function testAddress()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            fopen(__FILE__, 'rb')
        );

        $address = $object->getAddress();

        $this->assertNotNull($address);
        $this->assertSame($bucket->getPrefix() . 'target', $address);
    }

    public function testPutStreamIntegrity()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            $content = $this->generateStream()
        );

        $this->assertTrue($object->exists());

        $content->rewind();
        $this->assertSame($content->getContents(), $object->getStream()->getContents());
    }

    public function testPutStreamLongNameIntegrity()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('targetDir/targetName'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'targetDir/targetName',
            $content = $this->generateStream()
        );

        $this->assertTrue($object->exists());

        $content->rewind();
        $this->assertSame($content->getContents(), $object->getStream()->getContents());
    }

    public function testPutFilenameIntegrity()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $this->assertTrue($object->exists());
        $this->assertSame(file_get_contents(__FILE__), $object->getStream()->getContents());
    }

    public function testPutResourceIntegrity()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            fopen(__FILE__, 'rb')
        );

        $this->assertTrue($object->exists());
        $this->assertSame(file_get_contents(__FILE__), $object->getStream()->getContents());
    }

    public function testSize()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $this->assertTrue($object->exists());
        $this->assertSame(filesize(__FILE__), $object->getSize());
    }

    public function testRename()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $this->assertTrue($object->exists());

        $object->rename('targetB');

        $this->assertSame('targetB', $object->getName());
        $this->assertSame($bucket->getPrefix() . 'targetB', $object->getAddress());
    }

    public function testDelete()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $this->assertTrue($object->exists());

        $object->delete();

        $this->assertFalse($object->exists());
    }

    public function testLocalFilename()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $this->assertTrue(file_exists($object->localFilename()));
        $object->delete();
    }

    public function testAddressToString()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $this->assertSame((string)$object, $object->getAddress());
    }

    public function testCopyInternal()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $this->assertTrue($object->exists());
        $object2 = $object->copy($this->getSecondaryBucket());
        $this->assertTrue($object2->exists());

        $this->assertSame('target', $object2->getName());
        $this->assertSame($this->getSecondaryBucket()->getPrefix() . 'target',
            $object2->getAddress());
    }

    public function testReplaceInternal()
    {
        $bucket = $this->getBucket();
        $this->assertFalse($bucket->exists('target'));

        $object = $this->getStorage()->put(
            $this->getBucket()->getName(),
            'target',
            __FILE__
        );

        $oldObject = clone $object;
        $object = $object->replace($this->getSecondaryBucket());

        $this->assertTrue($object->exists());
        $this->assertFalse($oldObject->exists());

        $this->assertSame('target', $object->getName());
        $this->assertSame(
            $this->getSecondaryBucket()->getPrefix() . 'target',
            $object->getAddress()
        );

        $object->delete();
    }
}