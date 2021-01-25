<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\StorageException;

class LocalSystemResolver extends AbstractResolver implements BucketResolverInterface
{
    protected const SERVER_INFO_CLASS = LocalInfo::class;

    /**
     * @var ServerInfoInterface|LocalInfo
     */
    protected ServerInfoInterface $serverInfo;

    public function buildUrl(string $filePath): ?string
    {
        return $this->serverInfo->getOption(LocalInfo::HOST) . $this->normalizePathForServer($filePath);
    }

    /**
     * @param string $bucketName
     *
     * @return string
     *
     * @throws StorageException
     */
    public function buildBucketPath(string $bucketName): string
    {
        if (!$this->serverInfo->hasBucket($bucketName)) {
            throw new StorageException(
                \sprintf('Bucket `%s` is not defined for server `%s`', $bucketName, $this->serverInfo->getName())
            );
        }

        return $this->serverInfo->getOption(LocalInfo::ROOT_DIR)
            . $this->serverInfo->getBucket($bucketName)->getDirectory();
    }
}
