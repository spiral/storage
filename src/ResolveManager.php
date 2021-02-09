<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\AdapterResolver;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class ResolveManager implements SingletonInterface, ResolveManagerInterface
{
    protected StorageConfig $storageConfig;

    /**
     * @var AdapterResolver\AdapterResolverInterface[]
     */
    protected array $resolvers = [];

    private Resolver\UriResolverInterface $uriResolver;

    private FilePathValidatorInterface $filePathValidator;

    public function __construct(
        StorageConfig $storageConfig,
        Resolver\UriResolverInterface $uriResolver,
        FilePathValidatorInterface $filePathValidator
    ) {
        $this->storageConfig = $storageConfig;
        $this->uriResolver = $uriResolver;
        $this->filePathValidator = $filePathValidator;
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
            $fileInfo = $this->uriResolver->parseUriToStructure($uri);

            if ($fileInfo instanceof AdapterResolver\DTO\UriStructure && $fileInfo->isIdentified()) {
                return $this->getResolver($fileInfo->serverName)
                    ->buildUrl($fileInfo->filePath);
            }
        } catch (StorageException $e) {
            if ($throwException) {
                throw $e;
            }
        } catch (\Throwable $e) {
            if ($throwException) {
                throw new ResolveException($e->getMessage(), $e->getCode(), $e);
            }
        }

        if ($throwException) {
            throw new ResolveException('Url can\'t be built by uri ' . $uri);
        }

        return null;
    }

    /**
     * @param string $serverKey
     *
     * @return AdapterResolver\ResolverInterface
     *
     * @throws ResolveException
     * @throws StorageException
     */
    protected function getResolver(string $serverKey): AdapterResolver\AdapterResolverInterface
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
    ): AdapterResolver\AdapterResolverInterface {
        $resolverClass = $serverInfo->getResolverClass();

        return new $resolverClass(
            $this->storageConfig,
            $this->filePathValidator,
            $serverInfo->getName()
        );
    }
}
