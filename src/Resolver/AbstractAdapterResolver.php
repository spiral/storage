<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\UriParserInterface;

abstract class AbstractAdapterResolver implements AdapterResolverInterface
{
    protected const FILE_SYSTEM_INFO_CLASS = '';

    protected FileSystemInfoInterface $fsInfo;

    protected UriParserInterface $uriParser;

    /**
     * @var BucketInfoInterface[]
     */
    protected array $buckets = [];

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
                    'Wrong file system info (`%s`) for resolver `%s`',
                    get_class($fsInfo),
                    static::class
                )
            );
        }

        $this->uriParser = $uriParser;

        $this->fsInfo = $fsInfo;
    }

    public function normalizeFilePathToUri(string $filePath): string
    {
        try {
            return $this->uriParser->parseUri($filePath)->path;
        } catch (UriException $e) {
            // if filePath is not uri we suppose it is short form of filepath - without fs part
        }

        return $filePath;
    }
}
