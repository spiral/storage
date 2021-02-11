<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\UriParserInterface;

abstract class AbstractAdapterResolver implements AdapterResolverInterface
{
    protected const SERVER_INFO_CLASS = '';

    protected ServerInfoInterface $serverInfo;

    protected UriParserInterface $uriParser;

    /**
     * @var BucketInfoInterface[]
     */
    protected array $buckets = [];

    /**
     * @param UriParserInterface $uriParser
     * @param StorageConfig $storageConfig
     * @param string $serverKey
     *
     * @throws StorageException
     */
    public function __construct(UriParserInterface $uriParser, StorageConfig $storageConfig, string $serverKey)
    {
        $requiredClass = static::SERVER_INFO_CLASS;

        $serverInfo = $storageConfig->buildServerInfo($serverKey);

        if (empty($requiredClass) || !$serverInfo instanceof $requiredClass) {
            throw new StorageException(
                \sprintf(
                    'Wrong server info (%s) for resolver %s',
                    get_class($serverInfo),
                    static::class
                )
            );
        }

        $this->uriParser = $uriParser;

        $this->serverInfo = $serverInfo;
        $this->buckets = $storageConfig->getServerBuckets($serverKey);
    }

    public function normalizeFilePathToUri(string $filePath): string
    {
        try {
            return $this->uriParser->parseUri($filePath)->path;
        } catch (UriException $e) {
            // if filePath is not uri we suppose it is short form of filepath - without server name
        }

        return $filePath;
    }
}
