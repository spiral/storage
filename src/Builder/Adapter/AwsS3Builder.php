<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;

/**
 * @property FileSystemInfoInterface|AwsS3Info $fsInfo
 */
class AwsS3Builder extends AbstractBuilder
{
    protected const FILE_SYSTEM_INFO_CLASS = AwsS3Info::class;

    public function buildSimple(): FilesystemAdapter
    {
        $adapterClass = $this->fsInfo->getAdapterClass();

        return new $adapterClass(
            $this->fsInfo->getClient(),
            $this->fsInfo->getOption(AwsS3Info::BUCKET_KEY)
        );
    }

    public function buildAdvanced(): FilesystemAdapter
    {
        $adapterClass = $this->fsInfo->getAdapterClass();

        return new $adapterClass(
            $this->fsInfo->getClient(),
            $this->fsInfo->getOption(AwsS3Info::BUCKET_KEY),
            $this->fsInfo->hasOption(AwsS3Info::PATH_PREFIX_KEY)
                ? $this->fsInfo->getOption(AwsS3Info::PATH_PREFIX_KEY)
                : '',
            $this->fsInfo->getVisibilityConverter()
        );
    }
}
