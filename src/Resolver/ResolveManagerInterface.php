<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

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
     * @param bool $canBeNullable
     *
     * @return ResolverInterface|null
     *
     * @throws StorageException
     */
    public function getResolver(string $serverKey, bool $canBeNullable = false): ?ResolverInterface;

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

    /**
     * @param string $filePath
     *
     * @return string|null
     *
     * @throws StorageException
     */
    public function buildUrl(string $filePath): ?string;

    public function parseFilePath(string $filePath): ServerFilePathStructure;

    public function buildServerFilePath(string $serverName, string $filePath): string;
}
