<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;

interface ResolveManagerInterface
{
    /**
     * @param string $serverKey
     *
     * @return ResolverInterface
     *
     * @throws ResolveException
     */
    public function getResolver(string $serverKey): ResolverInterface;

    /**
     * @throws ResolveException
     * @throws StorageException
     */
    public function initResolvers(): void;

    /**
     * @param string[] $files
     *
     * @return \Generator
     *
     * @throws ResolveException
     */
    public function buildUrlsList(array $files): \Generator;

    /**
     * @param string $filePath
     *
     * @return string|null
     *
     * @throws ResolveException
     */
    public function buildUrl(string $filePath): ?string;
}
