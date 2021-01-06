<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use Spiral\StorageEngine\StorageEngine;
use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Tests\Traits\ServerBuilderTrait;

class StorageEngineTest extends TestCase
{
    use ServerBuilderTrait;

    public function testIsInitiated(): void
    {
        $engine = new StorageEngine();

        $this->assertFalse($engine->isInitiated());
    }

    /**
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    public function testInit(): void
    {
        $engine = new StorageEngine();

        $engine->init(
            ['local' => $this->buildLocalServer()]
        );

        $this->assertTrue($engine->isInitiated());
    }
}
