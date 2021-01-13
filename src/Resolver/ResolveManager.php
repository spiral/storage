<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;

class ResolveManager
{
    public const FILE_PATH_SERVER_PART = 'server';
    public const FILE_PATH_PATH_PART = 'path';

    public const FILE_PATH_PATTERN = '/^(?\'' . self::FILE_PATH_SERVER_PART . '\'[\w\-]*):\/\/(?\''
    . self::FILE_PATH_PATH_PART . '\'[\w\-\/\.]*)$/';

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
            if (!empty($fileInfo)) {
                $resolver = $this->getResolver($fileInfo[self::FILE_PATH_SERVER_PART]);

                yield $resolver->buildUrl($fileInfo[self::FILE_PATH_PATH_PART]);
            }
        }
    }

    public function parseFilePath(string $filePath): ?array
    {
        preg_match_all(static::FILE_PATH_PATTERN, $filePath, $matches, PREG_SET_ORDER);

        return !empty($matches) ? reset($matches) : null;
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
            default:
                throw new StorageException('No resolver was detected for driver ' . $serverInfo->getDriver());
        }
    }
}
