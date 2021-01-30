<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Traits;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;

trait AwsS3ServerBuilderTrait
{
    /**
     * @param string|null $name
     *
     * @return AwsS3Info
     *
     * @throws StorageException
     */
    protected function buildAwsS3Info(?string $name = ServerTestInterface::SERVER_NAME): AwsS3Info
    {
        return new AwsS3Info($name, $this->buildAwsS3ServerDescription());
    }

    protected function buildAwsS3ServerDescription(): array
    {
        return [
            AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
            AwsS3Info::OPTIONS_KEY => [
                AwsS3Info::BUCKET_KEY => 'debugBucket',
                AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
            ],
        ];
    }

    protected function getAwsS3Client(): S3Client
    {
        return new S3Client([
            'credentials' => new Credentials('someKey', 'someSecret'),
            'version' => 'latest',
            'region' => 'west',
        ]);
    }

    protected function getAwsS3VisibilityOption(): array
    {
        return [
            AwsS3Info::CLASS_KEY => PortableVisibilityConverter::class,
            AwsS3Info::OPTIONS_KEY => [
                AwsS3Info::VISIBILITY_KEY => Visibility::PUBLIC,
            ]
        ];
    }
}
