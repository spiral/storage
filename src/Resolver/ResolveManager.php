<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class ResolveManager implements SingletonInterface, ResolveManagerInterface
{
    protected StorageConfig $storageConfig;

    /**
     * @var ResolverInterface[]
     */
    protected array $resolvers = [];

    private FilePathResolverInterface $filePathResolver;

    private FilePathValidatorInterface $filePathValidator;

    public function __construct(
        StorageConfig $storageConfig,
        FilePathResolverInterface $filePathResolver,
        FilePathValidatorInterface $filePathValidator
    ) {
        $this->storageConfig = $storageConfig;
        $this->filePathResolver = $filePathResolver;
        $this->filePathValidator = $filePathValidator;
    }

    /**
     * @inheritDoc
     */
    public function getResolver(string $serverKey): ResolverInterface
    {
        if (!array_key_exists($serverKey, $this->resolvers)) {
            $this->resolvers[$serverKey] = $this->prepareResolverByServerInfo(
                $this->storageConfig->buildServerInfo($serverKey)
            );
        }

        return $this->resolvers[$serverKey];
    }

    /**
     * @inheritDoc
     */
    public function buildUrlsList(array $files, bool $throwException = true): \Generator
    {
        foreach ($files as $filePath) {
            yield $this->buildUrl($filePath, $throwException);
        }
    }

    /**
     * @inheritDoc
     */
    public function buildUrl(string $filePath, bool $throwException = true): ?string
    {
        try {
            $fileInfo = $this->filePathResolver->parseServerFilePathToStructure($filePath);

            if ($fileInfo instanceof ServerFilePathStructure && $fileInfo->isIdentified()) {
                return $this->getResolver($fileInfo->serverName)
                    ->buildUrl($fileInfo->filePath);
            }
        } catch (ResolveException | StorageException $e) {
            if ($throwException) {
                throw $e;
            }
        }

        if ($throwException) {
            throw new ResolveException('Url can\'t be built by filepath ' . $filePath);
        }

        return null;
    }

    /**
     * @param ServerInfoInterface $serverInfo
     *
     * @return ResolverInterface
     *
     * @throws ResolveException
     * @throws StorageException
     */
    protected function prepareResolverByServerInfo(ServerInfoInterface $serverInfo): ResolverInterface
    {
        switch ($serverInfo->getAdapterClass()) {
            case \League\Flysystem\Local\LocalFilesystemAdapter::class:
                return new LocalSystemResolver($serverInfo, $this->filePathValidator);
            case \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class:
            case \League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter::class:
                return new AwsS3Resolver($serverInfo, $this->filePathValidator);
            default:
                throw new ResolveException(
                    'No resolver was detected by provided adapter for server ' . $serverInfo->getName()
                );
        }
    }
}
