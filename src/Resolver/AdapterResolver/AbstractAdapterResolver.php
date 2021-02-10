<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver\AdapterResolver;

use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Resolver\DTO\UriStructure;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

abstract class AbstractAdapterResolver implements AdapterResolverInterface
{
    protected const SERVER_INFO_CLASS = '';

    protected FilePathValidatorInterface $filePathValidator;

    protected ServerInfoInterface $serverInfo;

    /**
     * @var BucketInfoInterface[]
     */
    protected array $buckets = [];

    /**
     * @param StorageConfig $storageConfig
     * @param FilePathValidatorInterface $filePathValidator
     * @param string $serverKey
     *
     * @throws StorageException
     */
    public function __construct(
        StorageConfig $storageConfig,
        FilePathValidatorInterface $filePathValidator,
        string $serverKey
    ) {
        $requiredClass = static::SERVER_INFO_CLASS;

        $serverInfo = $storageConfig->buildServerInfo($serverKey);

        if (empty($requiredClass) || !$serverInfo instanceof $requiredClass) {
            throw new StorageException(
                \sprintf(
                    'Wrong server info (%s) for resolver %s',
                    get_class($serverInfo),
                    static::class
                )
            );
        }

        $this->filePathValidator = $filePathValidator;

        $this->serverInfo = $serverInfo;

        $this->buckets = $storageConfig->getServerBuckets($serverKey);
    }

    public function normalizeFilePathToUri(string $filePath): string
    {
        try {
            $this->filePathValidator->validateUri($filePath);

            $uriStructure = new UriStructure(
                $filePath,
                $this->filePathValidator->getUriPattern()
            );

            return $uriStructure->isIdentified() ? $uriStructure->filePath : $filePath;
        } catch (ValidationException $e) {
            // if filePath is not uri we suppose it is short form of filepath - without server name
        }

        return $filePath;
    }
}
