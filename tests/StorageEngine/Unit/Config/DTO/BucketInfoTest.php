<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO;

use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Traits\LocalFsBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class BucketInfoTest extends AbstractUnitTest
{
    use LocalFsBuilderTrait;

    /**
     * @throws StorageException
     */
    public function testGetDirectory(): void
    {
        $directory = '/files/debug/';

        $localInfo = $this->buildLocalInfo();

        $dtoNull = new BucketInfo('dBucket', $localInfo->getName());

        $this->assertNull($dtoNull->getDirectory());

        $dto = new BucketInfo(
            'dBucket2',
            $localInfo->getName(),
            [
                'directory' => $directory,
                'server' => $localInfo->getName(),
            ]
        );

        $this->assertEquals($directory, $dto->getDirectory());
    }
}
