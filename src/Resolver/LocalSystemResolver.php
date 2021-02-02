<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;

class LocalSystemResolver extends AbstractResolver implements BucketResolverInterface
{
    protected const SERVER_INFO_CLASS = LocalInfo::class;

    /**
     * @var ServerInfoInterface|LocalInfo
     */
    protected ServerInfoInterface $serverInfo;

    /**
     * @param string $uri
     *
     * @return string|null
     *
     * @throws ResolveException
     */
    public function buildUrl(string $uri): ?string
    {
        if (!$this->serverInfo->hasOption(LocalInfo::HOST_KEY)) {
            throw new ResolveException(
                \sprintf('Url can\'t be built for server %s - host was not defined', $this->serverInfo->getName())
            );
        }

        return \sprintf(
            '%s%s',
            $this->serverInfo->getOption(LocalInfo::HOST_KEY),
            $this->normalizeFilePathToUri($uri)
        );
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
        if (!($bucket = $this->getBucketInfo($bucketName)) instanceof BucketInfoInterface) {
            throw new StorageException(
                \sprintf('Bucket `%s` is not defined for server `%s`', $bucketName, $this->serverInfo->getName())
            );
        }

        return \sprintf(
            '%s%s',
            $this->serverInfo->getOption(LocalInfo::ROOT_DIR_KEY),
            $bucket->getDirectory()
        );
    }

    public function getBucketInfo(string $key): ?BucketInfoInterface
    {
        return array_key_exists($key, $this->buckets) ? $this->buckets[$key] : null;
    }
}
