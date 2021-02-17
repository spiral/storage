<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Builder;

use Spiral\StorageEngine\Builder\Adapter\LocalBuilder;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Traits\AwsS3FsBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class LocalBuilderTest extends AbstractUnitTest
{
    use AwsS3FsBuilderTrait;

    public function testWrongServerInfoFailed(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Wrong filesystem info `%s` provided for `%s`', AwsS3Info::class, LocalBuilder::class)
        );

        new LocalBuilder($this->buildAwsS3Info());
    }
}
