<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO;

use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class BucketInfoTest extends AbstractUnitTest
{
    public function testGetDirectory(): void
    {
        $directory = '/files/debug/';

        $dtoNull = new BucketInfo(
            'dBucket',
            'dServer'
        );

        $this->assertNull($dtoNull->getDirectory());

        $dto = new BucketInfo(
            'dBucket',
            'dServer',
            [
                'options' => [
                    $this->getProtectedConst(BucketInfo::class, 'DIRECTORY_KEY') => $directory,
                ],
            ]
        );

        $this->assertEquals($directory, $dto->getDirectory());
    }
}
