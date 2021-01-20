<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;

class ResolveManager implements ResolveManagerInterface
{
    protected StorageConfig $storageConfig;

    /**
     * @var ResolverInterface[]
     */
    protected array $resolvers = [];

    public function __construct(StorageConfig $storageConfig)
    {
        $this->storageConfig = $storageConfig;
    }

    /**
     * @inheritDoc
     */
    public function getResolver(string $serverKey, bool $canBeNullable = false): ?ResolverInterface
    {
        if (!array_key_exists($serverKey, $this->resolvers)) {
            if ($canBeNullable) {
                return null;
            }

            throw new StorageException('No resolver was detected for server ' . $serverKey);
        }

        return $this->resolvers[$serverKey];
    }

    /**
     * @inheritDoc
     */
    public function initResolvers(): void
    {
        foreach ($this->storageConfig->getServersKeys() as $serverKey) {
            $this->resolvers[$serverKey] = $this->prepareResolverByServerInfo(
                $this->storageConfig->buildServerInfo($serverKey)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function buildUrlsList(array $files): \Generator
    {
        foreach ($files as $filePath) {
            yield $this->buildUrl($filePath);
        }
    }

    /**
     * @inheritDoc
     */
    public function buildUrl(string $filePath): ?string
    {
        $fileInfo = $this->parseFilePath($filePath);
        if ($fileInfo->isIdentified() && ($resolver = $this->getResolver($fileInfo->serverName, true))) {
            return $resolver->buildUrl($fileInfo->filePath);
        }

        return null;
    }

    public function parseFilePath(string $filePath): ServerFilePathStructure
    {
        return new ServerFilePathStructure($filePath);
    }

    public function buildServerFilePath(string $serverName, string $filePath): string
    {
        return \sprintf('%s%s%s', $serverName, static::SERVER_PATH_SEPARATOR, $filePath);
    }

    /**
     * @param ServerInfoInterface $serverInfo
     *
     * @return ResolverInterface
     *
     * @throws StorageException
     */
    protected function prepareResolverByServerInfo(ServerInfoInterface $serverInfo): ResolverInterface
    {
        switch ($serverInfo->getDriver()) {
            case AdapterName::LOCAL:
                return new LocalSystemResolver($serverInfo);
            case AdapterName::AWS_S3:
                return new AwsS3Resolver($serverInfo);
            default:
                throw new StorageException('No resolver was detected for driver ' . $serverInfo->getDriver());
        }
    }
}
