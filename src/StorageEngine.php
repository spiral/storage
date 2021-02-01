<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\MountManager;
use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\UriResolverInterface;

class StorageEngine implements StorageInterface, SingletonInterface
{
    protected ?MountManager $mountManager = null;

    private UriResolverInterface $uriResolver;

    public function __construct(UriResolverInterface $uriResolver)
    {
        $this->uriResolver = $uriResolver;
    }

    /**
     * @param array<string, FilesystemOperator> $servers
     */
    public function init(array $servers): void
    {
        $this->mountManager = new MountManager($servers);
    }

    /**
     * @param bool $throwException
     *
     * @return bool
     *
     * @throws StorageException
     */
    public function isInitiated($throwException = true): bool
    {
        $result = $this->mountManager instanceof FilesystemOperator;

        if (!$result && $throwException) {
            throw new StorageException('Storage engine was not initiated!');
        }

        return $result;
    }

    public function getMountManager(): ?FilesystemOperator
    {
        return $this->mountManager;
    }

    /**
     * @param string $uri
     *
     * @return bool
     *
     * @throws StorageException
     */
    public function fileExists(string $uri): bool
    {
        try {
            $this->isInitiated();

            $uriStructure = $this->uriResolver->parseUriToStructure($uri);

            return $this->getMountManager()->fileExists(
                $this->uriResolver->buildUri(
                    $uriStructure->serverName,
                    $uriStructure->filePath
                )
            );
        } catch (StorageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new StorageException($e->getMessage(), $e->getCode());
        }
    }
}
