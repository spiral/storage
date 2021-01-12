<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Exception\StorageException;

class AwsS3Builder extends AbstractBuilder
{
    /**
     * @return FilesystemAdapter
     *
     * @throws StorageException
     */
    public function buildSimple(): FilesystemAdapter
    {
        if (!$this->serverInfo instanceof AwsS3Info) {
            throw new StorageException('Wrong server info provided for AwsS3 builder');
        }

        $adapterClass = $this->serverInfo->getAdapterClass();

        return new $adapterClass(
            $this->serverInfo->getClient(),
            $this->serverInfo->getOption(AwsS3Info::BUCKET_NAME)
        );
    }

    /**
     * @return FilesystemAdapter
     *
     * @throws StorageException
     */
    public function buildAdvanced(): FilesystemAdapter
    {
        if (!$this->serverInfo instanceof AwsS3Info) {
            throw new StorageException('Wrong server info provided for AwsS3 builder');
        }

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
