<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\ResolveException;

class LocalSystemResolver extends AbstractAdapterResolver
{
    protected const SERVER_INFO_CLASS = LocalInfo::class;

    /**
     * @var ServerInfoInterface|LocalInfo
     */
    protected ServerInfoInterface $serverInfo;

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
        if (!$this->serverInfo->hasOption(LocalInfo::HOST_KEY)) {
            throw new ResolveException(
                \sprintf('Url can\'t be built for server %s - host was not defined', $this->serverInfo->getName())
            );
        }

        return \sprintf(
            '%s%s',
            $this->serverInfo->getOption(LocalInfo::HOST_KEY),
            $this->normalizeFilePathToUri($uri)
        );
    }
}
