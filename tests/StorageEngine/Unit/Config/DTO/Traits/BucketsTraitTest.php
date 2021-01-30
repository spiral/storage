<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\Traits\BucketsTrait;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class BucketsTraitTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;

    /**
     * @var MockObject|BucketsTrait
     */
    private $traitMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traitMock = $this->getMockForTrait(BucketsTrait::class);
    }

    /**
     * @throws \ReflectionException
     * @throws StorageException
     */
    public function testGetBucket(): void
    {
        $bucketName = 'debugBucket1';
        $bucket = new BucketInfo(
            $bucketName,
            $this->buildLocalInfo(),
            [BucketInfo::OPTIONS_KEY => [BucketInfo::DIRECTORY_KEY => '/debug/bucket1/']]
        );

        $this->callNotPublicMethod(
            $this->traitMock,
            'addBucket',
            [$bucket]
        );

        $this->assertSame($bucket, $this->traitMock->getBucket($bucketName));

        $this->assertNull($this->traitMock->getBucket('missedBucket'));
    }

    /**
     * @throws \ReflectionException
     * @throws StorageException
     */
    public function testHasBucket(): void
    {
        $bucketName = 'debugBucket1';
        $bucketName2 = 'debugBucket2';

        $localInfo = $this->buildLocalInfo();

        $this->callNotPublicMethod(
            $this->traitMock,
            'addBucket',
            [
                new BucketInfo(
                    $bucketName,
                    $localInfo,
                    [BucketInfo::OPTIONS_KEY => [BucketInfo::DIRECTORY_KEY => '/debug/bucket1/']]
                )
            ]
        );

        $this->callNotPublicMethod(
            $this->traitMock,
            'addBucket',
            [
                new BucketInfo(
                    $bucketName2,
                    $localInfo,
                    [BucketInfo::OPTIONS_KEY => [BucketInfo::DIRECTORY_KEY => '/debug/bucket2/']]
                )
            ]
        );

        $this->assertTrue($this->traitMock->hasBucket($bucketName));
        $this->assertTrue($this->traitMock->hasBucket($bucketName2));

        $this->assertFalse($this->traitMock->hasBucket('missedBucked'));
    }
}
