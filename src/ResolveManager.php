<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Parser\UriParserInterface;
use Spiral\StorageEngine\Resolver;

class ResolveManager implements SingletonInterface, ResolveManagerInterface
{
    protected StorageConfig $storageConfig;

    protected UriParserInterface $uriParser;

    /**
     * @var Resolver\AdapterResolverInterface[]
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

            return $this->getResolver($uriStructure->fileSystem)
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

    /**
     * Get resolver for filesystem by key
     *
     * @param string $fileSystem
     *
     * @return Resolver\AdapterResolverInterface
     *
     * @throws StorageException
     */
    protected function getResolver(string $fileSystem): Resolver\AdapterResolverInterface
    {
        if (!array_key_exists($fileSystem, $this->resolvers)) {
            $this->resolvers[$fileSystem] = $this->prepareResolverForFileSystem(
                $this->storageConfig->buildFileSystemInfo($fileSystem)
            );
        }

        return $this->resolvers[$fileSystem];
    }

    /**
     * Prepare resolver by provided filesystem info
     *
     * @param FileSystemInfoInterface $fsInfo
     *
     * @return Resolver\AdapterResolverInterface
     */
    protected function prepareResolverForFileSystem(FileSystemInfoInterface $fsInfo): Resolver\AdapterResolverInterface
    {
        $resolverClass = $fsInfo->getResolverClass();

        return new $resolverClass($this->uriParser, $this->storageConfig, $fsInfo->getName());
    }
}
