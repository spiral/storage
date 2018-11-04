<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Tests;

use Psr\Http\Message\StreamInterface;
use Spiral\Boot\FinalizerInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\ObjectInterface;
use Spiral\Storage\Server\AmazonServer;
use Spiral\Storage\StorageBucket;

class ExternalTest extends StorageTest
{
    /**
     * @expectedException \Spiral\Storage\Exception\ResolveException
     */
    public function testFailBucketLocation()
    {
        $this->assertFalse($this->getStorage()->open('invalid')->exists());
    }

    public function testInjections()
    {
        $b = self::$c->get(BucketInterface::class, 'amazon');
        $this->assertSame('amazon', $b->getName());
    }

    /**
     * @expectedException \Spiral\Storage\Exception\StorageException
     */
    public function testInjectionsEx()
    {
        $b = self::$c->get(BucketInterface::class);
        print_r($b);
    }

    public function testBucketAccess()
    {
        $this->assertSame(
            'amazon',
            $this->getStorage()->open('aws:target')->getBucket()->getName()
        );

        $this->assertSame(
            'files',
            $this->getStorage()->open('file:target')->getBucket()->getName()
        );

        $this->assertSame(
            'sftp',
            $this->getStorage()->open('sftp:target')->getBucket()->getName()
        );

        $this->assertSame(
            'ftp',
            $this->getStorage()->open('ftp:target')->getBucket()->getName()
        );

        $this->assertSame(
            'mongo',
            $this->getStorage()->open('mongo:target')->getBucket()->getName()
        );
    }

    public function testReplaceInStorages()
    {
        $this->assertFalse($this->getStorage()->open('aws:target')->exists());

        $content = $this->generateStream();

        $object = $this->getStorage()->put('amazon', 'target', $content);

        $this->assertSame('aws:target', $object->getAddress());
        $this->assertSame('amazon', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //FTP
        $object->replace($this->getStorage()->getBucket('ftp'));
        $this->assertFalse($this->getStorage()->open('aws:target')->exists());

        $this->assertSame('ftp:target', $object->getAddress());
        $this->assertSame('ftp', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //SFTP
        $object->replace($this->getStorage()->getBucket('sftp'));
        $this->assertFalse($this->getStorage()->open('ftp:target')->exists());

        $this->assertSame('sftp:target', $object->getAddress());
        $this->assertSame('sftp', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //GridFS
        $object->replace($this->getStorage()->getBucket('mongo'));
        $this->assertFalse($this->getStorage()->open('sftp:target')->exists());

        $this->assertSame('mongo:target', $object->getAddress());
        $this->assertSame('mongo', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        //Local
        $object->replace($this->getStorage()->getBucket('files'));
        $this->assertFalse($this->getStorage()->open('mongo:target')->exists());

        $this->assertSame('file:target', $object->getAddress());
        $this->assertSame('files', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        $object->replace($this->getStorage()->getBucket('amazon'));
        $this->assertFalse($this->getStorage()->open('file:target')->exists());

        $this->assertSame('aws:target', $object->getAddress());
        $this->assertSame('amazon', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        $object->delete();

        $this->assertFalse($object->exists());
    }

    public function testCopyInStorages()
    {
        $object = $this->getStorage()->open('aws:target');
        $this->assertFalse($object->exists());

        $content = $this->generateStream();

        $object = $this->getStorage()->put('amazon', 'target', $content);

        $ftpObject = $object->copy($this->getStorage()->getBucket('ftp'));
        $sftpObject = $object->copy($this->getStorage()->getBucket('sftp'));
        $gridObject = $object->copy($this->getStorage()->getBucket('mongo'));
        $localObject = $object->copy($this->getStorage()->getBucket('files'));

        $this->assertSame('aws:target', $object->getAddress());
        $this->assertSame('amazon', $object->getBucket()->getName());
        $this->assertContent($content, $object);

        $this->assertSame('ftp:target', $ftpObject->getAddress());
        $this->assertSame('ftp', $ftpObject->getBucket()->getName());
        $this->assertContent($content, $ftpObject);

        $this->assertSame('sftp:target', $sftpObject->getAddress());
        $this->assertSame('sftp', $sftpObject->getBucket()->getName());
        $this->assertContent($content, $sftpObject);

        $this->assertSame('mongo:target', $gridObject->getAddress());
        $this->assertSame('mongo', $gridObject->getBucket()->getName());
        $this->assertContent($content, $gridObject);

        $this->assertSame('file:target', $localObject->getAddress());
        $this->assertSame('files', $localObject->getBucket()->getName());
        $this->assertContent($content, $localObject);

        $object->delete();
        $ftpObject->delete();
        $gridObject->delete();
        $localObject->delete();
        $sftpObject->delete();

        self::$c->get(FinalizerInterface::class)->finalize();
    }

    public function testGetServer()
    {
        $this->assertInstanceOf(AmazonServer::class, $this->getStorage()->getServer('amazon'));
    }

    /**
     * @expectedException \Spiral\Storage\Exception\StorageException
     */
    public function testGetServerEx()
    {
        $this->getStorage()->getServer('other');
    }

    public function testGetBucket()
    {
        $this->assertInstanceOf(
            AmazonServer::class,
            $this->getStorage()->getBucket('amazon')->getServer()
        );
    }

    /**
     * @expectedException \Spiral\Storage\Exception\StorageException
     */
    public function testGetBucketEx()
    {
        $this->getStorage()->getBucket('other');
    }


    /**
     * @expectedException \Spiral\Storage\Exception\StorageException
     */
    public function testGetBucketEx2()
    {
        $this->getStorage()->getBucket('');
    }

    public function testAddBucket()
    {
        $this->getStorage()->addBucket(new StorageBucket(
            $this->getStorage()->getServer('amazon'),
            'aws-3',
            'aws3:',
            [
                'bucket' => 'aws-1'
            ]
        ));

        $this->assertInstanceOf(
            AmazonServer::class,
            $this->getStorage()->getBucket('aws-3')->getServer()
        );
    }

    /**
     * @expectedException \Spiral\Storage\Exception\StorageException
     */
    public function testAddBucketEx()
    {
        $this->getStorage()->addBucket(new StorageBucket(
            $this->getStorage()->getServer('amazon'),
            'amazon',
            'aws3:',
            [
                'bucket' => 'aws-1'
            ]
        ));
    }

    protected function assertContent(StreamInterface $stream, ObjectInterface $object)
    {
        $stream->rewind();
        $this->assertSame($stream->getContents(), $object->getStream()->getContents());
    }
}