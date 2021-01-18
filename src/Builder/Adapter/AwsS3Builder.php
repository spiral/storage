<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;

/**
 * @property ServerInfoInterface|AwsS3Info $serverInfo
 */
class AwsS3Builder extends AbstractBuilder
{
    protected const SERVER_INFO_CLASS = AwsS3Info::class;

    public function buildSimple(): FilesystemAdapter
    {
        $adapterClass = $this->serverInfo->getAdapterClass();

        return new $adapterClass(
            $this->serverInfo->getClient(),
            $this->serverInfo->getOption(AwsS3Info::BUCKET_NAME)
        );
    }

    public function buildAdvanced(): FilesystemAdapter
    {
        $adapterClass = $this->serverInfo->getAdapterClass();

        return new $adapterClass(
            $this->serverInfo->getClient(),
            $this->serverInfo->getOption(AwsS3Info::BUCKET_NAME),
            $this->serverInfo->hasOption(AwsS3Info::PATH_PREFIX)
                ? $this->serverInfo->getOption(AwsS3Info::PATH_PREFIX)
                : '',
            $this->serverInfo->getVisibiltyConverter()
        );
    }
}
