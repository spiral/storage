<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Builder;

use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use PHPUnit\Framework\MockObject\MockObject;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfo;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Traits\AwsS3ServerBuilderTrait;
use Spiral\StorageEngine\Tests\Traits\LocalServerBuilderTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class AdapterFactoryTest extends AbstractUnitTest
{
    use LocalServerBuilderTrait;
    use AwsS3ServerBuilderTrait;

    /**
     * @throws StorageException
     */
    public function testBuildSimpleLocalServer(): void
    {
        $info = $this->buildLocalInfo();

        $adapter = AdapterFactory::build($info);

        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBuildAdvancedLocalServer(): void
    {
        $options = [
            LocalInfo::ROOT_DIR => ServerTestInterface::ROOT_DIR,
            LocalInfo::HOST => ServerTestInterface::CONFIG_HOST,
            LocalInfo::WRITE_FLAGS => LOCK_NB,
            LocalInfo::LINK_HANDLING => LocalFilesystemAdapter::SKIP_LINKS,
            LocalInfo::VISIBILITY => [
                'file' => [
                    'public' => 0777,
                    'private' => 0644,
                ],
                'dir' => [
                    'public' => 0776,
                    'private' => 0444,
                ],
            ],
        ];

        $info = new LocalInfo(
            'debugLocalServer',
            [
                LocalInfo::ADAPTER => LocalFilesystemAdapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::LOCAL,
                LocalInfo::OPTIONS_KEY => $options,
            ]
        );

        $adapter = AdapterFactory::build($info);

        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);


        $this->assertEquals(
            $options[LocalInfo::LINK_HANDLING],
            $this->getProtectedProperty($adapter, 'linkHandling')
        );
        $this->assertEquals(
            $options[LocalInfo::WRITE_FLAGS],
            $this->getProtectedProperty($adapter, 'writeFlags')
        );
        $this->assertEquals(
            PortableVisibilityConverter::fromArray($options[LocalInfo::VISIBILITY]),
            $this->getProtectedProperty($adapter, 'visibility')
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBuildSimpleAwsS3Server(): void
    {
        $serverDescription = $this->buildAwsS3ServerDescription();
        $serverInfo = new AwsS3Info('awsS3', $serverDescription);

        $adapter = AdapterFactory::build($serverInfo);

        $this->assertInstanceOf(AwsS3V3Adapter::class, $adapter);

        $this->assertEquals(
            $serverDescription[AwsS3Info::OPTIONS_KEY][AwsS3Info::BUCKET],
            $this->getProtectedProperty($adapter, 'bucket')
        );
        $this->assertSame(
            $serverInfo->getClient(),
            $this->getProtectedProperty($adapter, 'client')
        );
    }

    /**
     * @throws StorageException
     * @throws \ReflectionException
     */
    public function testBuildAdvancedAwsS3Server(): void
    {
        $options = [
            AwsS3Info::BUCKET => 'testBucket',
            AwsS3Info::CLIENT => $this->getAwsS3ClientDescription(),
            AwsS3Info::PATH_PREFIX => '/some/prefix/',
            AwsS3Info::VISIBILITY => $this->getAwsS3VisibilityOption(),
        ];

        $info = new AwsS3Info(
            'debugAwsS3Server',
            [
                LocalInfo::ADAPTER => AwsS3V3Adapter::class,
                LocalInfo::DRIVER_KEY => AdapterName::AWS_S3,
                LocalInfo::OPTIONS_KEY => $options,
            ]
        );

        $adapter = AdapterFactory::build($info);

        $this->assertInstanceOf(AwsS3V3Adapter::class, $adapter);


        $this->assertEquals(
            new PathPrefixer($options[AwsS3Info::PATH_PREFIX]),
            $this->getProtectedProperty($adapter, 'prefixer')
        );
        $this->assertEquals(
            $info->getVisibiltyConverter(),
            $this->getProtectedProperty($adapter, 'visibility')
        );
    }

    public function testWrongServerInfoUsage(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Adapter can\'t be built by server info');

        /** @var MockObject|ServerInfo $info */
        $info = $this->getMockForAbstractClass(
            ServerInfo::class,
            [
                'someName',
                [
                    ServerInfo::ADAPTER => LocalFilesystemAdapter::class,
                    ServerInfo::DRIVER_KEY => AdapterName::LOCAL,
                    ServerInfo::OPTIONS_KEY => [],
                ],
            ]
        );

        AdapterFactory::build($info);
    }
}
