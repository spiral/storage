<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Tests\Traits\ReflectionHelperTrait;

abstract class AbstractUnitTest extends TestCase
{
    use ReflectionHelperTrait;
}
