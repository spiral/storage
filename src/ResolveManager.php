<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Parser\UriParserInterface;
use Spiral\StorageEngine\Resolver\AdapterResolver;

class ResolveManager implements SingletonInterface, ResolveManagerInterface
{
    protected StorageConfig $storageConfig;

    protected UriParserInterface $uriParser;

    /**
     * @var \Spiral\StorageEngine\Resolver\AdapterResolverInterface[]
     */
    protected array $resolvers = [];

    public function __construct(StorageConfig $storageConfig, UriParserInterface $uriParser)
    {
        $this->storageConfig = $storageConfig;
        $this->uriParser = $uriParser;
    }

    /**
     * @inheritDoc
     */
    public function buildUrlsList(array $files, bool $throwException = true): \Generator
    {
        foreach ($files as $uri) {
            yield $this->buildUrl($uri, $throwException);
        }
    }

    /**
     * @inheritDoc
     */
    public function buildUrl(string $uri, bool $throwException = true): ?string
    {
        try {
            $uriStructure = $this->uriParser->parseUri($uri);

            return $this->getResolver($uriStructure->server)
                ->buildUrl($uriStructure->path);
        } catch (StorageException $e) {
            if ($throwException) {
                throw $e;
            }
        } catch (\Throwable $e) {
            if ($throwException) {
                throw new ResolveException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return null;
    }

    public function buildBucketUri(string $bucket, string $filePath): string
    {
        $bucketInfo = $this->storageConfig->buildBucketInfo($bucket);

        return (string)$this->uriParser->prepareUri(
            $bucketInfo->getServerKey(),
            \sprintf('%s%s', $bucketInfo->getDirectory(), $filePath)
        );
    }

    /**
     * @param string $serverKey
     *
     * @return \Spiral\StorageEngine\Resolver\AdapterResolverInterface
     *
     * @throws StorageException
     */
    protected function getResolver(string $serverKey): Resolver\AdapterResolverInterface
    {
        if (!array_key_exists($serverKey, $this->resolvers)) {
            $this->resolvers[$serverKey] = $this->prepareResolverForServer(
                $this->storageConfig->buildServerInfo($serverKey)
            );
        }

        return $this->resolvers[$serverKey];
    }

    protected function prepareResolverForServer(
        ServerInfoInterface $serverInfo
    ): Resolver\AdapterResolverInterface {
        $resolverClass = $serverInfo->getResolverClass();

        return new $resolverClass($this->uriParser, $this->storageConfig, $serverInfo->getName());
    }
}
