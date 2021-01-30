<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Validation;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Exception\ValidationException;

class FilePathValidator implements SingletonInterface, FilePathValidatorInterface
{
    public const SERVER_PATTERN = '(?\'' . self::FILE_PATH_SERVER_PART . '\'[\w\-]*)';
    public const FILE_PATH_PATTERN = '(?\'' . self::FILE_PATH_PART . '\'[\w\-+_\(\)\/\.,=\*\s]*)';

    public const SERVER_FILE_PATH_PATTERN = '/^' . self::SERVER_PATTERN . ':\/\/' . self::FILE_PATH_PATTERN . '$/';

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
     * @param string $filePath
     *
     * @throws ValidationException
     */
    public function validateServerFilePath(string $filePath): void
    {
        if (!preg_match($this->getServerFilePathPattern(), $filePath)) {
            throw new ValidationException('Server file path is not suitable by format');
        }
    }

    public function getFilePathPattern(): string
    {
        return static::FILE_PATH_PATTERN;
    }

    public function getServerFilePathPattern(): string
    {
        return static::SERVER_FILE_PATH_PATTERN;
    }
}
