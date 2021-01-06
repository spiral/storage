<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

abstract class AbstractResolver implements ResolverInterface
{
    public const FILE_PATH_SERVER_PART = 'server';
    public const FILE_PATH_PATH_PART = 'path';

    protected const FILE_PATH_PATTERN = '/^(?\'' . self::FILE_PATH_SERVER_PART . '\'[\w\-]*):\/\/(?\''
    . self::FILE_PATH_PATH_PART . '\'[\w\-\/\.]*)$/';

    public function parseFilePath(string $filePath): ?array
    {
        preg_match_all(static::FILE_PATH_PATTERN, $filePath, $matches, PREG_SET_ORDER);

        return !empty($matches) ? reset($matches) : null;
    }
}
