<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use Spiral\Core\Container\SingletonInterface;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver as Resolver;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class ResolveManager implements SingletonInterface, ResolveManagerInterface
{
    protected StorageConfig $storageConfig;

    /**
     * @var Resolver\ResolverInterface[]
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

            if ($fileInfo instanceof Resolver\DTO\UriStructure && $fileInfo->isIdentified()) {
                return $this->getResolver($fileInfo->serverName)
                    ->buildUrl($fileInfo->filePath);
            }
        } catch (ConfigException | StorageException $e) {
            if ($throwException) {
                throw $e;
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
     * @return Resolver\ResolverInterface
     *
     * @throws ResolveException
     * @throws StorageException
     */
    protected function getResolver(string $serverKey): Resolver\ResolverInterface
    {
        if (!array_key_exists($serverKey, $this->resolvers)) {
            $this->resolvers[$serverKey] = $this->prepareResolverByServerInfo(
                $this->storageConfig->buildServerInfo($serverKey)
            );
        }

        return $this->resolvers[$serverKey];
    }

    /**
     * @param ServerInfoInterface $serverInfo
     *
     * @return Resolver\ResolverInterface
     *
     * @throws ResolveException
     * @throws StorageException
     */
    protected function prepareResolverByServerInfo(ServerInfoInterface $serverInfo): Resolver\ResolverInterface
    {
        switch ($serverInfo->getAdapterClass()) {
            case \League\Flysystem\Local\LocalFilesystemAdapter::class:
                return new Resolver\LocalSystemResolver($serverInfo, $this->filePathValidator);
            case \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class:
            case \League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter::class:
                return new Resolver\AwsS3Resolver($serverInfo, $this->filePathValidator);
            default:
                throw new ResolveException(
                    'No resolver was detected by provided adapter for server ' . $serverInfo->getName()
                );
        }
    }
}
