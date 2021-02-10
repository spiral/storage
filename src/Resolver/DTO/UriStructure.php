<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver\DTO;

use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Validation\FilePathValidatorInterface;

class UriStructure
{
    public ?string $serverName = null;

    public ?string $filePath = null;

    /**
     * @param string $uri
     * @param string $pattern
     *
     * @throws ValidationException
     */
    public function __construct(string $uri, string $pattern)
    {
        preg_match($pattern, $uri, $match);

        if (empty($match)) {
            throw new ValidationException('No uri structure was detected in uri ' . $uri);
        }

        if (
            !array_key_exists(FilePathValidatorInterface::FILE_PATH_SERVER_PART, $match)
            || empty($match[FilePathValidatorInterface::FILE_PATH_SERVER_PART])
        ) {
            throw new ValidationException('No server was detected in uri ' . $uri);
        }

        if (
            !array_key_exists(FilePathValidatorInterface::FILE_PATH_PART, $match)
            || empty($match[FilePathValidatorInterface::FILE_PATH_PART])
        ) {
            throw new ValidationException('No path was detected in uri ' . $uri);
        }

        $this->serverName = $match[FilePathValidatorInterface::FILE_PATH_SERVER_PART];
        $this->filePath = $match[FilePathValidatorInterface::FILE_PATH_PART];
    }
}
