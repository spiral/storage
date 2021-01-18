<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Builder;

use Spiral\StorageEngine\Builder\Adapter\AwsS3Builder;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class AwsS3BuilderTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    public function testWrongServerInfoFailed(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Wrong server info %s provided for %s', LocalInfo::class, AwsS3Builder::class)
        );

        new AwsS3Builder($this->buildLocalInfo());
    }
}
