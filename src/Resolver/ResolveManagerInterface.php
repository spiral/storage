<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;

interface ResolveManagerInterface
{
    /**
     * @param string $serverKey
     *
     * @return ResolverInterface
     *
     * @throws StorageException
     */
    public function getResolver(string $serverKey): ResolverInterface;

    /**
     * @throws StorageException
     */
    public function initResolvers(): void;

    /**
     * @param string[] $files
     *
     * @return \Generator
     *
     * @throws StorageException
     */
    public function buildUrlsList(array $files): \Generator;

    public function parseFilePath(string $filePath): ServerFilePathStructure;
}
