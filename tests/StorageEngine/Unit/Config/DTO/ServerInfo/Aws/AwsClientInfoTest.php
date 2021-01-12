<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo\Aws;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsClientInfo;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class AwsClientInfoTest extends AbstractUnitTest
{
    /**
     * @throws StorageException
     */
    public function testConstructor(): void
    {
        $credentials = new Credentials('someKey', 'someSecret');
        $info = new AwsClientInfo(
            [
                AwsClientInfo::CLASS_KEY => S3Client::class,
                AwsClientInfo::OPTIONS_KEY => [
                    'credentials' => $credentials,
                ]
            ]
        );

        $this->assertEquals(S3Client::class, $info->getClass());
        $this->assertEquals($credentials, $info->getOption('credentials'));
    }

    public function testGetClient(): void
    {
        $credentials = new Credentials('someKey', 'someSecret');
        $info = new AwsClientInfo(
            [
                AwsClientInfo::CLASS_KEY => S3Client::class,
                AwsClientInfo::OPTIONS_KEY => [
                    'version' => 'latest',
                    'credentials' => $credentials,
                    'region' => 'west',
                ]
            ]
        );

        $s3Client = $info->getClient();
        $this->assertInstanceOf(S3Client::class, $s3Client);
        $this->assertSame($s3Client, $info->getClient());
    }

    /**
     * @throws StorageException
     */
    public function testClassNotDefinedFailed(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Aws client must be described with s3 client class');

        $credentials = new Credentials('someKey', 'someSecret');

        new AwsClientInfo(
            [
                AwsClientInfo::OPTIONS_KEY => ['credentials' => $credentials]
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testOptionsNotDefinedFailed(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Aws client must be described with s3 client options');

        new AwsClientInfo(
            [AwsClientInfo::CLASS_KEY => S3Client::class]
        );
    }

    /**
     * @throws StorageException
     */
    public function testOptionsEmptyFailed(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Aws client must be described with s3 client options');

        new AwsClientInfo(
            [
                AwsClientInfo::CLASS_KEY => S3Client::class,
                AwsClientInfo::OPTIONS_KEY => [],
            ]
        );
    }
}
