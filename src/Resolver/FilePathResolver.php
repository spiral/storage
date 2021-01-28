<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class FilePathResolver implements FilePathResolverInterface
{
    public const SERVER_PATH_SEPARATOR = '://';

    private FilePathValidatorInterface $filePathValidator;

    public function __construct(FilePathValidatorInterface $filePathValidator)
    {
        $this->filePathValidator = $filePathValidator;
    }

    /**
     * @param string $serverKey
     * @param string $filePath
     *
     * @return string
     *
     * @throws ResolveException
     */
    public function buildServerFilePath(string $serverKey, string $filePath): string
    {
        try {
            $this->filePathValidator->validateServerFilePath($filePath);

            throw new ResolveException(
                \sprintf('Filepath %s already contains server key', $filePath)
            );
        } catch (ValidationException $e) {
        }

        return \sprintf(
            '%s%s%s',
            $serverKey,
            static::SERVER_PATH_SEPARATOR,
            $filePath
        );
    }

    public function parseServerFilePathToStructure(string $filePath): ?ServerFilePathStructure
    {
        try {
            $this->filePathValidator->validateServerFilePath($filePath);

            return new ServerFilePathStructure(
                $filePath,
                $this->filePathValidator->getServerFilePathPattern()
            );
        } catch (ValidationException $e) {
        }

        return null;
    }
}
