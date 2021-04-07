<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage;

use Spiral\Storage\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\Storage\Config\StorageConfig;
use Spiral\Storage\Exception\ResolveException;
use Spiral\Storage\Exception\StorageException;
use Spiral\Storage\Parser\UriParser;
use Spiral\Storage\Parser\UriParserInterface;
use Spiral\Storage\Resolver\AdapterResolverInterface;

/**
 * @psalm-import-type UriLikeType from UriResolverInterface
 */
class UriResolver implements UriResolverInterface
{
    /**
     * @var StorageConfig
     */
    protected $storageConfig;

    /**
     * @var UriParserInterface
     */
    protected $parser;

    /**
     * @var array<AdapterResolverInterface>
     */
    protected $resolvers = [];

    /**
     * @param StorageConfig $storageConfig
     * @param UriParserInterface|null $parser
     */
    public function __construct(StorageConfig $storageConfig, UriParserInterface $parser = null)
    {
        $this->storageConfig = $storageConfig;
        $this->parser = $parser ?? new UriParser();
    }

    /**
     * {@inheritDoc}
     */
    public function resolveAll(iterable $uris): iterable
    {
        foreach ($uris as $uri) {
            yield $this->resolve($uri);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($uri)
    {
        try {
            $uri = $this->parser->parse($uri);

            return $this->getResolver($uri->getFileSystem())
                ->buildUrl($uri->getPath())
            ;
        } catch (StorageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ResolveException($e->getMessage(), (int)$e->getCode(), $e);
        }
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
        if (!\array_key_exists($fileSystem, $this->resolvers)) {
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
        $resolver = $fsInfo->getResolverClass();

        return new $resolver($this->parser, $this->storageConfig, $fsInfo->getName());
    }
}
