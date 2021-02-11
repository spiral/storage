<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\FileSystemInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\StorageEngine\Exception\ResolveException;

class LocalSystemResolver extends AbstractAdapterResolver
{
    protected const FILE_SYSTEM_INFO_CLASS = LocalInfo::class;

    /**
     * @var FileSystemInfoInterface|LocalInfo
     */
    protected FileSystemInfoInterface $fsInfo;

    /**
     * @param string $uri
     * @param array $options
     *
     * @return string|null
     *
     * @throws ResolveException
     */
    public function buildUrl(string $uri, array $options = []): ?string
    {
        if (!$this->fsInfo->hasOption(LocalInfo::HOST_KEY)) {
            throw new ResolveException(
                \sprintf('Url can\'t be built for file system %s - host was not defined', $this->fsInfo->getName())
            );
        }

        return \sprintf(
            '%s%s',
            $this->fsInfo->getOption(LocalInfo::HOST_KEY),
            $this->normalizeFilePathToUri($uri)
        );
    }
}
