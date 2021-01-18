<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;

class ResolveManager
{
    private StorageConfig $storageConfig;

    /**
     * @var ResolverInterface[]
     */
    private array $resolvers = [];

    public function __construct(StorageConfig $storageConfig)
    {
        $this->storageConfig = $storageConfig;
    }

    /**
     * @param string $serverKey
     *
     * @return ResolverInterface
     *
     * @throws StorageException
     */
    public function getResolver(string $serverKey): ResolverInterface
    {
        if (!array_key_exists($serverKey, $this->resolvers)) {
            throw new StorageException('No resolver was detected for server ' . $serverKey);
        }

        return $this->resolvers[$serverKey];
    }

    /**
     * @throws StorageException
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
     * @param string[] $files
     *
     * @return \Generator
     *
     * @throws StorageException
     */
    public function buildUrlsList(array $files): \Generator
    {
        foreach ($files as $filePath) {
            $fileInfo = $this->parseFilePath($filePath);
            if ($fileInfo->isIdentified()) {
                $resolver = $this->getResolver($fileInfo->serverName);

                yield $resolver->buildUrl($fileInfo->filePath);
            }
        }
    }

    public function parseFilePath(string $filePath): ServerFilePathStructure
    {
        return new ServerFilePathStructure($filePath);
    }

    /**
     * @param ServerInfoInterface $serverInfo
     *
     * @return ResolverInterface
     *
     * @throws StorageException
     */
    private function prepareResolverByServerInfo(ServerInfoInterface $serverInfo): ResolverInterface
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
