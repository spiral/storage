<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

interface StorageReaderInterface
{
    public function fileExists(string $uri): bool;

    public function read(string $uri): string;

    public function readStream(string $uri);

    public function lastModified(string $uri): int;

    public function fileSize(string $uri): int;

    public function mimeType(string $uri): string;

    public function visibility(string $uri): string;
}
