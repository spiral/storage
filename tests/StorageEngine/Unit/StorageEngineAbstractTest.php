<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\StorageEngine;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\StorageConfigTrait;

abstract class StorageEngineAbstractTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use StorageConfigTrait;

    /**
     * @return StorageEngine
     *
     * @throws StorageException
     */
    protected function buildSimpleStorageEngine(): StorageEngine
    {
        return new StorageEngine($this->buildStorageConfig(), $this->getUriResolver());
    }
}
