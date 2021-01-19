<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver\DTO;

use Spiral\StorageEngine\Resolver\ResolveManagerInterface;

class ServerFilePathStructure
{
    public ?string $serverName = null;

    public ?string $filePath = null;

    public static function isServerFilePath(string $filePath): bool
    {
        return strpos($filePath, ResolveManagerInterface::SERVER_PATH_SEPARATOR) !== false;
    }

    public function __construct(string $filePath)
    {
        preg_match_all(ResolveManagerInterface::FILE_PATH_PATTERN, $filePath, $matches, PREG_SET_ORDER);

        if (count($matches) > 0) {
            $match = $matches[0];
            if (array_key_exists(ResolveManagerInterface::FILE_PATH_SERVER_PART, $match)) {
                $this->serverName = $match[ResolveManagerInterface::FILE_PATH_SERVER_PART];
            }

            if (array_key_exists(ResolveManagerInterface::FILE_PATH_PATH_PART, $match)) {
                $this->filePath = $match[ResolveManagerInterface::FILE_PATH_PATH_PART];
            }
        }
    }

    public function isIdentified(): bool
    {
        return $this->serverName !== null && $this->filePath !== null;
    }
}
