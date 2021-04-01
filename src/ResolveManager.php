<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage;

use Spiral\Core\Container\SingletonInterface;
use Spiral\Storage\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\Storage\Config\StorageConfig;
use Spiral\Storage\Exception\ResolveException;
use Spiral\Storage\Exception\StorageException;
use Spiral\Storage\Parser\UriParserInterface;
use Spiral\Storage\Resolver\AdapterResolverInterface;

class ResolveManager implements SingletonInterface, ResolveManagerInterface
{
    /**
     * @var StorageConfig
     */
    protected $storageConfig;

    /**
     * @var UriParserInterface
     */
    protected $uriParser;

    /**
     * @var array<AdapterResolverInterface>
     */
    protected $resolvers = [];

    /**
     * @param StorageConfig $storageConfig
     * @param UriParserInterface $uriParser
     */
    public function __construct(StorageConfig $storageConfig, UriParserInterface $uriParser)
    {
        $this->storageConfig = $storageConfig;
        $this->uriParser = $uriParser;
    }

    /**
     * {@inheritDoc}
     */
    public function buildUrlsList(array $files, bool $throwException = true): iterable
    {
        foreach ($files as $uri) {
            yield $this->buildUrl($uri, $throwException);
        }
    }

    /**
     * {@inheritDoc}
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
                throw new ResolveException($e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return null;
    }

    /**
     * Get resolver for filesystem by key
     *
     * @param string $fileSystem
     *
     * @return AdapterResolverInterface
     *
     * @throws StorageException
     */
    protected function getResolver(string $fileSystem): AdapterResolverInterface
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
     * @return AdapterResolverInterface
     */
    protected function prepareResolverForFileSystem(FileSystemInfoInterface $fsInfo): AdapterResolverInterface
    {
        $resolverClass = $fsInfo->getResolverClass();

        return new $resolverClass($this->uriParser, $this->storageConfig, $fsInfo->getName());
    }
}
