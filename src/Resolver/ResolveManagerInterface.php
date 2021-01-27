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
     * @throws StorageException
     */
    public function getResolver(string $serverKey): ResolverInterface;

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
     * @param bool $useException
     *
     * @return string|null
     *
     * @throws ResolveException
     */
    public function buildUrl(string $filePath, bool $useException): ?string;
}
