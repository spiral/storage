<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Parser;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\DTO\UriStructure;
use Spiral\StorageEngine\Parser\DTO\UriStructureInterface;

class UriParser implements UriParserInterface, SingletonInterface
{
    /**
     * Name of filepath block name in regex patter
     */
    protected const FILE_PATH_PART = 'path';

    /**
     * Name of filesystem name block name in regex patter
     */
    protected const FILE_PATH_FS_PART = 'fs';

    /**
     * Separator between filesystem name and filepath
     */
    protected const FS_PATH_SEPARATOR = '://';

    /**
     * File system name pattern block
     */
    protected const FILE_SYSTEM_PATTERN = '(?\'' . self::FILE_PATH_FS_PART . '\'[\w\-]*)';

    /**
     * Filepath pattern block
     */
    protected const FILE_PATH_PATTERN = '(?\'' . self::FILE_PATH_PART . '\'[\w\-+_\(\)\/\.,=\*\s]*)';

    /**
     * Full uri pattern
     */
    protected const URI_PATTERN = '/^' . self::FILE_SYSTEM_PATTERN . ':\/\/' . self::FILE_PATH_PATTERN . '$/';

    /**
     * @inheritDoc
     */
    public function prepareUri(string $fs, string $path): UriStructureInterface
    {
        return $this->buildUriStructure($fs, $path);
    }

    /**
     * @inheritDoc
     */
    public function parseUri(string $uri): UriStructureInterface
    {
        preg_match(static::URI_PATTERN, $uri, $match);

        if (empty($match)) {
            throw new UriException(\sprintf('No uri structure was detected in uri `%s`', $uri));
        }

        if (
            !array_key_exists(static::FILE_PATH_FS_PART, $match)
            || empty($match[static::FILE_PATH_FS_PART])
        ) {
            throw new UriException(\sprintf('No filesystem was detected in uri `%s`', $uri));
        }

        if (
            !array_key_exists(static::FILE_PATH_PART, $match)
            || empty($match[static::FILE_PATH_PART])
        ) {
            throw new UriException(\sprintf('No path was detected in uri `%s`', $uri));
        }

        return $this->buildUriStructure(
            $match[static::FILE_PATH_FS_PART],
            $match[static::FILE_PATH_PART]
        );
    }

    /**
     * Build uris structure object by provided filesystem name and path
     * Can be built with another separator
     *
     * @param string $fs
     * @param string $path
     * @param string|null $separator
     *
     * @return UriStructureInterface
     */
    protected function buildUriStructure(string $fs, string $path, ?string $separator = null): UriStructureInterface
    {
        return new UriStructure($fs, $path, $separator ?? self::FS_PATH_SEPARATOR);
    }
}
