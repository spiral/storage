<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver\DTO;

class ServerFilePathStructure
{
    private const SERVER_PATH_SEPARATOR = '://';

    private const FILE_PATH_PATH_PART = 'path';
    private const FILE_PATH_SERVER_PART = 'server';
    private const FILE_PATH_PATTERN = '/^(?\'' . self::FILE_PATH_SERVER_PART . '\'[\w\-]*):\/\/(?\''
    . self::FILE_PATH_PATH_PART . '\'[\w\-\/\.]*)$/';

    public ?string $serverName = null;

    public ?string $filePath = null;

    public static function isServerFilePath(string $filePath): bool
    {
        return strpos($filePath, static::SERVER_PATH_SEPARATOR) !== false;
    }

    public function __construct(string $filePath)
    {
        preg_match_all(static::FILE_PATH_PATTERN, $filePath, $matches, PREG_SET_ORDER);

        if (count($matches) > 0) {
            $match = $matches[0];
            if (array_key_exists(static::FILE_PATH_SERVER_PART, $match)) {
                $this->serverName = $match[static::FILE_PATH_SERVER_PART];
            }

            if (array_key_exists(static::FILE_PATH_PATH_PART, $match)) {
                $this->filePath = $match[static::FILE_PATH_PATH_PART];
            }
        }
    }

    public function isIdentified(): bool
    {
        return $this->serverName !== null && $this->filePath !== null;
    }
}
