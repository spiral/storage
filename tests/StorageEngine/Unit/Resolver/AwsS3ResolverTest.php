<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use Aws\CommandInterface;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Psr\Http\Message\RequestInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AwsS3Resolver;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class AwsS3ResolverTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use AwsS3ServerBuilderTrait;

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

        new AwsS3Resolver(
            new StorageConfig(
                [
                    'servers' => [
                        'local' => $this->buildLocalInfoDescription(),
                        'aws' => $this->buildAwsS3ServerDescription(),
                    ]
                ]
            ),
            $this->getFilePathValidator(),
            'local'
        );
    }

    /**
     * @throws StorageException
     */
    public function testBuildUrl(): void
    {
        $serverName = 'aws';
        $uri = 'http://some-host.com/somefile.txt';

        $commandMock = $this->createMock(CommandInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);

        $requestMock->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri);

        $s3Client = $this->createMock(S3Client::class);

        $s3Client->expects($this->exactly(2))
            ->method('createPresignedRequest')
            ->willReturn($requestMock);

        $s3Client->expects($this->exactly(2))
            ->method('getCommand')
            ->willReturn($commandMock);

        $serverDescription = [
            AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
            AwsS3Info::OPTIONS_KEY => [
                AwsS3Info::BUCKET_KEY => 'debugBucket',
                AwsS3Info::CLIENT_KEY => $s3Client,
            ],
        ];

        $resolver = new AwsS3Resolver(
            new StorageConfig(
                ['servers' => [$serverName => $serverDescription]]
            ),
            $this->getFilePathValidator(),
            $serverName
        );

        $this->assertEquals($uri, $resolver->buildUrl('somefile.txt'));

        $this->assertEquals($uri, $resolver->buildUrl('somefile.txt', [AwsS3Resolver::EXPIRES_OPTION => '+1hour']));
    }
}
