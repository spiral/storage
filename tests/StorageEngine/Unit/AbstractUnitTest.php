<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use Spiral\StorageEngine\Tests\AbstractTest;
use Spiral\StorageEngine\Tests\Traits\ReflectionHelperTrait;

abstract class AbstractUnitTest extends AbstractTest
{
    use ReflectionHelperTrait;
}
