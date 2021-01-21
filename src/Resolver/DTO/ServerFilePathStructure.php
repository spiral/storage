<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver\DTO;

use Spiral\StorageEngine\Validation\FilePathValidator;

class ServerFilePathStructure
{
    public ?string $serverName = null;

    public ?string $filePath = null;

    public function __construct(string $filePath)
    {
        preg_match(FilePathValidator::SERVER_FILE_PATH_PATTERN, $filePath, $match);

        if (!empty($match)) {
            if (array_key_exists(FilePathValidator::FILE_PATH_SERVER_PART, $match)) {
                $this->serverName = $match[FilePathValidator::FILE_PATH_SERVER_PART];
            }

            if (array_key_exists(FilePathValidator::FILE_PATH_PART, $match)) {
                $this->filePath = $match[FilePathValidator::FILE_PATH_PART];
            }
        }
    }

    public function isIdentified(): bool
    {
        return $this->serverName !== null && $this->filePath !== null;
    }
}
