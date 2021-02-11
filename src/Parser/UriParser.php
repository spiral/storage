<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Parser;

use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\DTO\UriStructure;
use Spiral\StorageEngine\Parser\DTO\UriStructureInterface;

class UriParser implements UriParserInterface, SingletonInterface
{
    protected const FILE_PATH_PART = 'path';
    protected const FILE_PATH_SERVER_PART = 'server';

    protected const SERVER_PATH_SEPARATOR = '://';

    protected const SERVER_PATTERN = '(?\'' . self::FILE_PATH_SERVER_PART . '\'[\w\-]*)';
    protected const FILE_PATH_PATTERN = '(?\'' . self::FILE_PATH_PART . '\'[\w\-+_\(\)\/\.,=\*\s]*)';

    protected const URI_PATTERN = '/^' . self::SERVER_PATTERN . ':\/\/' . self::FILE_PATH_PATTERN . '$/';

    /**
     * @inheritDoc
     */
    public function prepareUri(string $server, string $path): UriStructureInterface
    {
        return $this->buildUriStructure($server, $path);
    }

    /**
     * @inheritDoc
     */
    public function parseUri(string $uri): UriStructureInterface
    {
        preg_match(static::URI_PATTERN, $uri, $match);

        if (empty($match)) {
            throw new UriException('No uri structure was detected in uri ' . $uri);
        }

        if (
            !array_key_exists(static::FILE_PATH_SERVER_PART, $match)
            || empty($match[static::FILE_PATH_SERVER_PART])
        ) {
            throw new UriException('No server was detected in uri ' . $uri);
        }

        if (
            !array_key_exists(static::FILE_PATH_PART, $match)
            || empty($match[static::FILE_PATH_PART])
        ) {
            throw new UriException('No path was detected in uri ' . $uri);
        }

        return $this->buildUriStructure(
            $match[static::FILE_PATH_SERVER_PART],
            $match[static::FILE_PATH_PART]
        );
    }

    protected function buildUriStructure(
        string $server,
        string $path,
        ?string $separator = null
    ): UriStructureInterface {
        return new UriStructure($server, $path, $separator ?? self::SERVER_PATH_SEPARATOR);
    }
}
