<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

abstract class AbstractResolver implements ResolverInterface
{
    protected const SERVER_INFO_CLASS = '';

    protected ServerInfoInterface $serverInfo;

    protected FilePathValidatorInterface $filePathValidator;

    /**
     * @param ServerInfoInterface $serverInfo
     * @param FilePathValidatorInterface $filePathValidator
     *
     * @throws StorageException
     */
    public function __construct(ServerInfoInterface $serverInfo, FilePathValidatorInterface $filePathValidator)
    {
        $requiredClass = static::SERVER_INFO_CLASS;

        if (empty($requiredClass) || !$serverInfo instanceof $requiredClass) {
            throw new StorageException(
                \sprintf(
                    'Wrong server info (%s) for resolver %s',
                    get_class($serverInfo),
                    static::class
                )
            );
        }

        $this->serverInfo = $serverInfo;
        $this->filePathValidator = $filePathValidator;
    }

    public function normalizePathForServer(string $filePath): string
    {
        try {
            $this->filePathValidator->validateServerFilePath($filePath);

            $filePathStructure = new ServerFilePathStructure(
                $filePath,
                $this->filePathValidator->getServerFilePathPattern()
            );

            return $filePathStructure->isIdentified() ? $filePathStructure->filePath : $filePath;
        } catch (ValidationException $e) {
            // if filePath is not server file path we supposes it is short form of filepath - without server name
        }

        return $filePath;
    }
}
