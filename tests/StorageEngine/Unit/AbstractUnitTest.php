<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;

abstract class AbstractUnitTest extends TestCase
{
    /**
     * @param string|object $object
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
        $class = is_string($object) ? $object : get_class($object);

        $refClass = new \ReflectionClass($class);

        $protectedProperty = $refClass->getProperty($property);
        $protectedProperty->setAccessible(true);

        return $protectedProperty->getValue($object);
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     *
     * @throws \ReflectionException
     */
    protected function setProtectedProperty($object, string $property, $value): void
    {
        $refClass = new \ReflectionClass(get_class($object));

        $protectedProperty = $refClass->getProperty($property);
        $protectedProperty->setAccessible(true);
        $protectedProperty->setValue($object, $value);
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
