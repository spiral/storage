<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;

interface ResolveManagerInterface
{
    public const SERVER_PATH_SEPARATOR = '://';

    public const FILE_PATH_PATH_PART = 'path';
    public const FILE_PATH_SERVER_PART = 'server';
    public const FILE_PATH_PATTERN = '/^(?\'' . self::FILE_PATH_SERVER_PART . '\'[\w\-]*):\/\/(?\''
    . self::FILE_PATH_PATH_PART . '\'[\w\-\/\.]*)$/';

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

    public function parseFilePath(string $filePath): ServerFilePathStructure;

    public function buildServerFilePath(string $serverName, string $filePath): string;
}
