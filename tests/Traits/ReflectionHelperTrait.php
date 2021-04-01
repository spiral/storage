<?php

declare(strict_types=1);

namespace Spiral\Tests\Storage\Traits;

trait ReflectionHelperTrait
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
        return $this->prepareReflectionClass($object)
            ->getConstant($constName);
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
        $refClass = $this->prepareReflectionClass($object);

        $protectedProperty = $refClass->getProperty($property);
        $protectedProperty->setAccessible(true);

        return $protectedProperty->getValue($object);
    }

    /**
     * @param mixed $object
     * @param string $property
     * @param mixed $value
     *
     * @throws \ReflectionException
     */
    protected function setProtectedProperty($object, string $property, $value): void
    {
        $refClass = $this->prepareReflectionClass($object);

        $protectedProperty = $refClass->getProperty($property);
        $protectedProperty->setAccessible(true);

        $protectedProperty->setValue($object, $value);
    }

    /**
     * @param mixed $object
     * @param string $method
     * @param array $args
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function callNotPublicMethod($object, string $method, array $args = [])
    {
        $refClass = $this->prepareReflectionClass($object);

        $method = $refClass->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    /**
     * @param mixed $object
     *
     * @return \ReflectionClass
     *
     * @throws \ReflectionException
     */
    protected function prepareReflectionClass($object): \ReflectionClass
    {
        return new \ReflectionClass(
            is_string($object) ? $object : get_class($object)
        );
    }
}
