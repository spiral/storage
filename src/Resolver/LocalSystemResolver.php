<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\StorageException;

class LocalSystemResolver extends AbstractResolver
{
    private StorageConfig $storageConfig;

    public function __construct(StorageConfig $storageConfig)
    {
        $this->storageConfig = $storageConfig;
    }

    /**
     * @param string[] $files
     *
     * @return \Generator
     *
     * @throws StorageException
     */
    public function buildUrlsList(array $files): \Generator
    {
        foreach ($files as $filePath) {
            $fileInfo = $this->parseFilePath($filePath);
            if (!empty($fileInfo)) {
                $serverInfo = $this->storageConfig->buildServerInfo($fileInfo[self::FILE_PATH_SERVER_PART]);

                if ($serverInfo->hasOption(LocalInfo::HOST)) {
                    yield $serverInfo->getOption(LocalInfo::HOST) . $fileInfo[self::FILE_PATH_PATH_PART];
                }
            }
        }
    }
}
