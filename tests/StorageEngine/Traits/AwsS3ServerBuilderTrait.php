<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Traits;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
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
        return new AwsS3Info(
            $name,
            [
                'class' => AwsS3V3Adapter::class,
                'options' => [
                    AwsS3Info::BUCKET_NAME => 'debugBucket',
                    'client' => $this->getClientInfoArray(),
                ],
            ]
        );
    }

    protected function getClientInfoArray(): array
    {
        return [
            'class' => S3Client::class,
            'options' => [
                'credentials' => new Credentials('someKey', 'someSecret'),
            ],
        ];
    }
}
