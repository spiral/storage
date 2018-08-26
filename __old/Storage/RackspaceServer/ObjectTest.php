<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Storage\RackspaceServer;

class ObjectTest extends \Spiral\Tests\Storage\ObjectTest
{
    use ServerTrait;

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
        $this->breakToken();
        $object = $object->replace($this->secondaryBucket());

        $this->assertTrue($object->exists());
        $this->assertFalse($oldObject->exists());

        $this->assertSame('target', $object->getName());
        $this->assertSame($this->secondaryBucket()->getPrefix() . 'target', $object->getAddress());

        $object->delete();
    }

    private function breakToken()
    {
        $reflection = new \ReflectionClass(get_class($this->server));
        $property = $reflection->getProperty('authToken');
        $property->setAccessible(true);
        $property->setValue($this->server, 'bad');
    }
}