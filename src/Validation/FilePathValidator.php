<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Validation;

use Spiral\StorageEngine\Exception\ValidationException;

class FilePathValidator
{
    public const FILE_PATH_PART = 'path';
    public const FILE_PATH_SERVER_PART = 'server';

    public const SERVER_PATTERN = '(?\'' . self::FILE_PATH_SERVER_PART . '\'[\w\-]*)';
    public const FILE_PATH_PATTERN = '(?\'' . self::FILE_PATH_PART . '\'[\w\-+_\(\)\/\.\*\s]*)';

    public const SERVER_FILE_PATH_PATTERN = '/^' . self::SERVER_PATTERN . ':\/\/' . self::FILE_PATH_PATTERN . '$/';

    /**
     * @param string $filePath
     *
     * @throws ValidationException
     *
     * @return bool
     */
    public static function validateFilePath(string $filePath): bool
    {
        if (!preg_match(\sprintf('/^%s$/', self::FILE_PATH_PATTERN), $filePath)) {
            throw new ValidationException('File name is not suitable by format');
        }

        return true;
    }

    /**
     * @param string $filePath
     *
     * @throws ValidationException
     *
     * @return bool
     */
    public static function validateServerFilePath(string $filePath): bool
    {
        if (!preg_match(static::SERVER_FILE_PATH_PATTERN, $filePath)) {
            throw new ValidationException('Server file path is not suitable by format');
        }

        return true;
    }
}
