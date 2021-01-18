<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO;

use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class BucketInfoTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    /**
     * @throws \ReflectionException
     * @throws \Spiral\StorageEngine\Exception\StorageException
     */
    public function testGetDirectory(): void
    {
        $directory = '/files/debug/';

        $localInfo = $this->buildLocalInfo();

        $dtoNull = new BucketInfo('dBucket', $localInfo);

        $this->assertNull($dtoNull->getDirectory());

        $dto = new BucketInfo(
            'dBucket2',
            $localInfo,
            [
                BucketInfo::OPTIONS_KEY => [
                    $this->getProtectedConst(BucketInfo::class, 'DIRECTORY_KEY') => $directory,
                ],
            ]
        );

        $this->assertEquals($directory, $dto->getDirectory());
    }
}
