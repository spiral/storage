<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder;

use League\Flysystem\FilesystemAdapter;
use Spiral\StorageEngine\Builder\Adapter as AdapterBuilder;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo;
use Spiral\StorageEngine\Exception\StorageException;

class AdapterFactory
{
    /**
     * Build filesystem adapter by provided filesystem info
     *
     * @param FileSystemInfo\FileSystemInfoInterface $info
     *
     * @return FilesystemAdapter
     *
     * @throws StorageException
     */
    public static function build(FileSystemInfo\FileSystemInfoInterface $info): FilesystemAdapter
    {
        $builder = static::detectAdapterBuilder($info);

        if ($info->isAdvancedUsage()) {
            return $builder->buildAdvanced();
        }

        return $builder->buildSimple();
    }

    /**
     * Detect required builder by filesystem info
     *
     * @param FileSystemInfo\FileSystemInfoInterface $info
     *
     * @return AdapterBuilder\AdapterBuilderInterface
     *
     * @throws StorageException
     */
    private static function detectAdapterBuilder(
        FileSystemInfo\FileSystemInfoInterface $info
    ): AdapterBuilder\AdapterBuilderInterface {
        switch (get_class($info)) {
            case FileSystemInfo\LocalInfo::class:
                return new AdapterBuilder\LocalBuilder($info);
            case FileSystemInfo\Aws\AwsS3Info::class:
                return new AdapterBuilder\AwsS3Builder($info);
            default:
                throw new StorageException(
                    \sprintf('Adapter can\'t be built by filesystem info `%s`', $info->getName())
                );
        }
    }
}
