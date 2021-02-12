<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Builder;

use Spiral\StorageEngine\Builder\Adapter\AwsS3Builder;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Traits\LocalFsBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class AwsS3BuilderTest extends AbstractUnitTest
{
    use LocalFsBuilderTrait;

    public function testWrongFsInfoFailed(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf('Wrong file system info `%s` provided for `%s`', LocalInfo::class, AwsS3Builder::class)
        );

        new AwsS3Builder($this->buildLocalInfo());
    }
}
