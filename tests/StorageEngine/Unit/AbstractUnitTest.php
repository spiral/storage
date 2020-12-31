<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;

abstract class AbstractUnitTest extends TestCase
{
    /**
     * @param mixed $object
     * @param string $constName
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function getProtectedConst($object, string $constName)
    {
        $class = is_string($object) ? $object : get_class($object);

        $refClass = new \ReflectionClass($class);

        return $refClass->getConstant($constName);
    }

    /**
     * @param mixed $object
     * @param string $property
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function getProtectedProperty($object, string $property)
    {
        $refClass = new \ReflectionClass(get_class($object));

        $protectedProperty = $refClass->getProperty($property);
        $protectedProperty->setAccessible(true);

        return $protectedProperty->getValue($object);
    }

    /**
     * @param $object
     * @param string $method
     * @param array $args
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function callNotPublicMethod($object, string $method, array $args = [])
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
