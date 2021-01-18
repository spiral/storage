<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Builder;

use Spiral\StorageEngine\Builder\Adapter\LocalBuilder;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalBuilderTest extends AbstractUnitTest
{
    use AwsS3ServerBuilderTrait;

    public function testWrongServerInfoFailed(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Wrong server info %s provided for %s', AwsS3Info::class, LocalBuilder::class)
        );

        new LocalBuilder($this->buildAwsS3Info());
    }
}
