<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\Traits;

use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;
use Spiral\StorageEngine\Config\DTO\Traits\ClassBasedTrait;

class ClassBasedTraitTest extends AbstractUnitTest
{
    /**
     * @var ClassBasedTrait
     */
    private $trait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trait = $this->getMockForTrait(ClassBasedTrait::class);
    }

    public function testSetClass(): void
    {
        $this->assertInstanceOf(get_class($this->trait), $this->trait->setClass(static::class));
    }

    public function testSetClassFailed(): void
    {
        $wrongClass = static::class . 1;

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Class %s not exists. %s', $wrongClass, '')
        );

        $this->trait->setClass($wrongClass);
    }

    public function testGetClass(): void
    {
        $this->trait->setClass(static::class);

        $this->assertEquals(static::class, $this->trait->getClass());
    }
}