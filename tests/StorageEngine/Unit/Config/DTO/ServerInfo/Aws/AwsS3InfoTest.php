<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\ServerInfo\Aws;

use Aws\S3\S3Client;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
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
            AwsS3Info::BUCKET_KEY => 'debugBucket',
            AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
        ];

        $serverInfo = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
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
            AwsS3Info::BUCKET_KEY => 'debugBucket',
            AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
        ];

        $serverInfo = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::ADAPTER_KEY => AsyncAwsS3Adapter::class,
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
            AwsS3Info::BUCKET_KEY => 'debugBucket',
            AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
            AwsS3Info::PATH_PREFIX_KEY => 'somePrefix',
            AwsS3Info::VISIBILITY_KEY => $this->getAwsS3VisibilityOption(),
        ];

        $serverInfo = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
                AwsS3Info::OPTIONS_KEY => $options,
            ]
        );

        $this->assertTrue($serverInfo->isAdvancedUsage());
        foreach ($options as $optionKey => $optionVal) {
            $this->assertEquals($optionVal, $serverInfo->getOption($optionKey));
        }

        $visibilityConvertor = $serverInfo->getVisibiltyConverter();
        $this->assertInstanceOf(PortableVisibilityConverter::class, $visibilityConvertor);
        $this->assertSame($visibilityConvertor, $serverInfo->getVisibiltyConverter());
    }

    /**
     * @throws StorageException
     */
    public function testAdvancedUsageAsync(): void
    {
        $options = [
            AwsS3Info::BUCKET_KEY => 'debugBucket',
            AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
            AwsS3Info::PATH_PREFIX_KEY => 'somePrefix',
            AwsS3Info::VISIBILITY_KEY => $this->getAwsS3VisibilityOption(),
        ];

        $advancedAwsS3Info = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::ADAPTER_KEY => AsyncAwsS3Adapter::class,
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
    public function testGetClient(): void
    {
        $options = [
            AwsS3Info::BUCKET_KEY => 'debugBucket',
            AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
        ];

        $serverInfo = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
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
     * @param string $serverName
     * @param array $options
     * @param string $exceptionMsg
     *
     * @throws StorageException
     */
    public function testValidateRequiredOptionsFailed(string $serverName, array $options, string $exceptionMsg): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($exceptionMsg);

        new AwsS3Info(
            $serverName,
            [
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
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
        $this->expectExceptionMessage(
            'Option visibility defined in wrong format for server someServer, array expected'
        );

        new AwsS3Info(
            'someServer',
            [
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_KEY => 'someBucket',
                    AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
                    AwsS3Info::VISIBILITY_KEY => 12,
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
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_KEY => 'someBucket',
                    AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
                    AwsS3Info::VISIBILITY_KEY => [
                        AwsS3Info::CLASS_KEY => PortableVisibilityConverter::class,
                        AwsS3Info::OPTIONS_KEY => [
                            AwsS3Info::VISIBILITY_KEY => 12,
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
        $this->expectExceptionMessage(
            'Option path-prefix defined in wrong format for server someServer, string expected'
        );

        new AwsS3Info(
            'someServer',
            [
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_KEY => 'someBucket',
                    AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
                    AwsS3Info::PATH_PREFIX_KEY => [1, 2],
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
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_KEY => 'debugBucket',
                    AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
                ],
            ]
        );

        $this->assertFalse($simpleAwsS3->isAdvancedUsage());

        $advancedAwsS3Info = new AwsS3Info(
            'someServer',
            [
                AwsS3Info::ADAPTER_KEY => AwsS3V3Adapter::class,
                AwsS3Info::OPTIONS_KEY => [
                    AwsS3Info::BUCKET_KEY => 'debugBucket',
                    AwsS3Info::CLIENT_KEY => $this->getAwsS3Client(),
                    AwsS3Info::PATH_PREFIX_KEY => 'somePrefix',
                ],
            ]
        );

        $this->assertTrue($advancedAwsS3Info->isAdvancedUsage());
    }

    public function getWrongUrlExpiresList(): array
    {
        $serverName = ServerTestInterface::SERVER_NAME;
        $errorMsgPrefix = 'Url expires should be string or DateTimeInterface implemented object for server ';

        return [
            [
                $serverName,
                [new \DateTime('+1 hour')],
                $errorMsgPrefix . ServerTestInterface::SERVER_NAME,
            ],
            [
                $serverName,
                null,
                $errorMsgPrefix . ServerTestInterface::SERVER_NAME,
            ],
            [
                'someServer',
                true,
                $errorMsgPrefix . 'someServer',
            ],
        ];
    }

    public function getMissedRequiredOptions(): array
    {
        $serverName = ServerTestInterface::SERVER_NAME;

        return [
            [
                $serverName,
                [],
                'Option bucket not detected for server ' . $serverName,
            ],
            [
                $serverName,
                [AwsS3Info::CLIENT_KEY => 'client'],
                'Option bucket not detected for server ' . $serverName,
            ],
            [
                'someServer',
                [AwsS3Info::BUCKET_KEY => 'someBucket'],
                'Option client not detected for server someServer',
            ],
        ];
    }
}
