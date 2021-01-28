<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver\DTO;

use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class ServerFilePathStructure
{
    public ?string $serverName = null;

    public ?string $filePath = null;

    public function __construct(string $filePath, string $pattern)
    {
        preg_match($pattern, $filePath, $match);

        if (!empty($match)) {
            if (array_key_exists(FilePathValidatorInterface::FILE_PATH_SERVER_PART, $match)) {
                $this->serverName = $match[FilePathValidatorInterface::FILE_PATH_SERVER_PART];
            }

            if (array_key_exists(FilePathValidatorInterface::FILE_PATH_PART, $match)) {
                $this->filePath = $match[FilePathValidatorInterface::FILE_PATH_PART];
            }
        }
    }

    public function isIdentified(): bool
    {
        return $this->serverName !== null && $this->filePath !== null;
    }
}
