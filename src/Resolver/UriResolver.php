<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Resolver\DTO\UriStructure;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class UriResolver implements UriResolverInterface, SingletonInterface
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
     * @throws ValidationException
     */
    public function buildUri(string $serverKey, string $filePath): string
    {
        $this->filePathValidator->validateFilePath($filePath);

        return \sprintf(
            '%s%s%s',
            $serverKey,
            static::SERVER_PATH_SEPARATOR,
            $filePath
        );
    }

    /**
     * @param string $uri
     *
     * @return UriStructure
     *
     * @throws ResolveException
     */
    public function parseUriToStructure(string $uri): UriStructure
    {
        try {
            $this->filePathValidator->validateUri($uri);

            return new UriStructure(
                $uri,
                $this->filePathValidator->getUriPattern()
            );
        } catch (ValidationException $e) {
            // if provided uri is not uri - structure can't be built
            throw new ResolveException(
                \sprintf('File %s can\'t be identified', $uri)
            );
        }
    }
}
