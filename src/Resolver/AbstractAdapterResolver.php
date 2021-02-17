<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\UriParserInterface;

/**
 * Abstract class for any resolver
 * Depends on adapter by default
 */
abstract class AbstractAdapterResolver implements AdapterResolverInterface
{
    /**
     * Filesystem info class required for resolver
     * In case other filesystem info will be provided - exception will be thrown
     */
    protected const FILE_SYSTEM_INFO_CLASS = '';

    protected FileSystemInfoInterface $fsInfo;

    protected UriParserInterface $uriParser;

    /**
     * @param UriParserInterface $uriParser
     * @param StorageConfig $storageConfig
     * @param string $fs
     *
     * @throws StorageException
     */
    public function __construct(UriParserInterface $uriParser, StorageConfig $storageConfig, string $fs)
    {
        $requiredClass = static::FILE_SYSTEM_INFO_CLASS;

        $fsInfo = $storageConfig->buildFileSystemInfo($fs);

        if (empty($requiredClass) || !$fsInfo instanceof $requiredClass) {
            throw new StorageException(
                \sprintf(
                    'Wrong filesystem info (`%s`) for resolver `%s`',
                    get_class($fsInfo),
                    static::class
                )
            );
        }

        $this->uriParser = $uriParser;

        $this->fsInfo = $fsInfo;
    }

    /**
     * Normalize filepath for filesystem operation
     * In case uri provided path to file will be extracted
     * In case filepath provided it will be returned
     *
     * @param string $filePath
     *
     * @return string
     */
    public function normalizeFilePath(string $filePath): string
    {
        try {
            return $this->uriParser->parseUri($filePath)->path;
        } catch (UriException $e) {
            // if filePath is not uri we suppose it is short form of filepath - without fs part
        }

        return $filePath;
    }
}
