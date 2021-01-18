<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo\Aws;

use Aws\S3\S3Client;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class AwsS3InfoTest extends AbstractUnitTest
{
    use AwsS3ServerBuilderTrait;

    /**
     * @throws StorageException
     */
    public function testValidateSimple(): void
    {
        $options = [
            AwsS3Info::BUCKET_NAME => 'debugBucket',
            AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
        ];

        $serverInfo = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => $options,
            ]
        );

        $this->assertEquals(AwsS3V3Adapter::class, $serverInfo->getAdapterClass());

        foreach ($options as $optionKey => $optionVal) {
            $this->assertEquals($optionVal, $serverInfo->getOption($optionKey));
        }

        $this->assertNull($serverInfo->getVisibiltyConverter());
    }

    /**
     * @throws StorageException
     */
    public function testValidateSimpleAsync(): void
    {
        $options = [
            AwsS3Info::BUCKET_NAME => 'debugBucket',
            AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
        ];

        $serverInfo = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AsyncAwsS3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => $options,
            ]
        );

        $this->assertEquals(AsyncAwsS3Adapter::class, $serverInfo->getAdapterClass());

        foreach ($options as $optionKey => $optionVal) {
            $this->assertEquals($optionVal, $serverInfo->getOption($optionKey));
        }
    }

    /**
     * @throws StorageException
     */
    public function testAdvancedUsage(): void
    {
        $options = [
            AwsS3Info::BUCKET_NAME => 'debugBucket',
            AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
            AwsS3Info::PATH_PREFIX => 'somePrefix',
            AwsS3Info::VISIBILITY => $this->getAwsS3VisibilityOption(),
        ];

        $advancedAwsS3Info = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => $options,
            ]
        );

        $this->assertTrue($advancedAwsS3Info->isAdvancedUsage());
        foreach ($options as $optionKey => $optionVal) {
            $this->assertEquals($optionVal, $advancedAwsS3Info->getOption($optionKey));
        }

        $visibilityConvertor = $advancedAwsS3Info->getVisibiltyConverter();
        $this->assertInstanceOf(PortableVisibilityConverter::class, $visibilityConvertor);
        $this->assertSame($visibilityConvertor, $advancedAwsS3Info->getVisibiltyConverter());
    }

    /**
     * @throws StorageException
     */
    public function testAdvancedUsageAsync(): void
    {
        $options = [
            AwsS3Info::BUCKET_NAME => 'debugBucket',
            AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
            AwsS3Info::PATH_PREFIX => 'somePrefix',
            AwsS3Info::VISIBILITY => $this->getAwsS3VisibilityOption(),
        ];

        $advancedAwsS3Info = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AsyncAwsS3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => $options,
            ]
        );

        $this->assertTrue($advancedAwsS3Info->isAdvancedUsage());
        foreach ($options as $optionKey => $optionVal) {
            $this->assertEquals($optionVal, $advancedAwsS3Info->getOption($optionKey));
        }

        $visibilityConvertor = $advancedAwsS3Info->getVisibiltyConverter();
        $this->assertInstanceOf(PortableVisibilityConverter::class, $visibilityConvertor);
        $this->assertSame($visibilityConvertor, $advancedAwsS3Info->getVisibiltyConverter());
    }

    public function testGetClient(): void
    {
        $options = [
            AwsS3Info::BUCKET_NAME => 'debugBucket',
            AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
        ];

        $serverInfo = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => $options,
            ]
        );

        $client = $serverInfo->getClient();
        $this->assertInstanceOf(S3Client::class, $client);
        $this->assertSame($client, $serverInfo->getClient());
    }

    /**
     * @dataProvider getMissedRequiredOptions
     *
     * @param array $options
     * @param string $exceptionMsg
     *
     * @throws StorageException
     */
    public function testValidateRequiredOptionsFailed(array $options, string $exceptionMsg): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($exceptionMsg);

        new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => $options,
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateVisibilityOptionWrongTypeFailed(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('visibility should be defined as array');

        new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_NAME => 'someBucket',
                    AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
                    AwsS3Info::VISIBILITY => 12,
                ],
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateVisibilityOptionWrongValueFailed(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('visibility should be defined with one of values: public,private');

        new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_NAME => 'someBucket',
                    AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
                    AwsS3Info::VISIBILITY => [
                        AwsS3Info::CLASS_KEY => PortableVisibilityConverter::class,
                        AwsS3Info::OPTIONS_KEY => [
                            AwsS3Info::VISIBILITY => 12,
                        ]
                    ],
                ],
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testValidateOptionalOptionsPathPrefixFailed(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('path-prefix should be defined as scalar value');

        new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_NAME => 'someBucket',
                    AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
                    AwsS3Info::PATH_PREFIX => [1, 2],
                ],
            ]
        );
    }

    /**
     * @throws StorageException
     */
    public function testIsAdvancedUsage(): void
    {
        $simpleAwsS3 = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_NAME => 'debugBucket',
                    AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
                ],
            ]
        );

        $this->assertFalse($simpleAwsS3->isAdvancedUsage());

        $advancedAwsS3Info = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::CLASS_KEY => AwsS3V3Adapter::class,
                AwsS3Info::DRIVER_KEY => AdapterName::AWS_S3,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_NAME => 'debugBucket',
                    AwsS3Info::CLIENT_NAME => $this->getAwsS3ClientDescription(),
                    AwsS3Info::PATH_PREFIX => 'somePrefix',
                ],
            ]
        );

        $this->assertTrue($advancedAwsS3Info->isAdvancedUsage());
    }

    public function getMissedRequiredOptions(): array
    {
        return [
            [
                [],
                'awsS3 server needs used bucket name defined',
            ],
            [
                ['option1' => 'optionVal'],
                'awsS3 server needs used bucket name defined',
            ],
            [
                [AwsS3Info::BUCKET_NAME => 'someBucket'],
                'awsS3 server needs S3 client description',
            ],
        ];
    }
}
