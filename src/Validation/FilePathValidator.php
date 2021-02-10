<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Validation;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Exception\ValidationException;

class FilePathValidator implements SingletonInterface, FilePathValidatorInterface
{
    protected const SERVER_PATH_SEPARATOR = '://';

    protected const SERVER_PATTERN = '(?\'' . self::FILE_PATH_SERVER_PART . '\'[\w\-]*)';
    protected const FILE_PATH_PATTERN = '(?\'' . self::FILE_PATH_PART . '\'[\w\-+_\(\)\/\.,=\*\s]*)';

    protected const URI_PATTERN = '/^' . self::SERVER_PATTERN . ':\/\/' . self::FILE_PATH_PATTERN . '$/';

    /**
     * @param string $filePath
     *
     * @throws ValidationException
     */
    public function validateFilePath(string $filePath): void
    {
        if (!preg_match(\sprintf('/^%s$/', $this->getFilePathPattern()), $filePath)) {
            throw new ValidationException('File path is not suitable by format');
        }
    }

    /**
     * @param string $uri
     *
     * @throws ValidationException
     */
    public function validateUri(string $uri): void
    {
        if (!preg_match($this->getUriPattern(), $uri)) {
            throw new ValidationException('Uri is not suitable by format');
        }
    }

    public function getFilePathPattern(): string
    {
        return static::FILE_PATH_PATTERN;
    }

    public function getUriPattern(): string
    {
        return static::URI_PATTERN;
    }

    public function getServerPathSeparator(): string
    {
        return static::SERVER_PATH_SEPARATOR;
    }
}
