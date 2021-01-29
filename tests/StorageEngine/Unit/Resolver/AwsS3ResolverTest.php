<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AwsS3Resolver;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;
use Spiral\StorageEngine\Validation\FilePathValidator;

class AwsS3ResolverTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    /**
     * @throws StorageException
     */
    public function testWrongServerInfo(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Wrong server info (%s) for resolver %s',
                LocalInfo::class,
                AwsS3Resolver::class
            )
        );

        new AwsS3Resolver($this->buildLocalInfo(), new FilePathValidator());
    }
}
