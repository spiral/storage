<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Validation;

use Spiral\StorageEngine\Exception\ValidationException;

interface FilePathValidatorInterface
{
    public const FILE_PATH_PART = 'path';
    public const FILE_PATH_SERVER_PART = 'server';

    public function getFilePathPattern(): string;

    public function getServerFilePathPattern(): string;

    /**
     * @param string $filePath
     *
     * @throws ValidationException
     */
    public function validateFilePath(string $filePath): void;

    /**
     * @param string $filePath
     *
     * @throws ValidationException
     */
    public function validateServerFilePath(string $filePath): void;
}
