<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

interface StorageWriterInterface
{
    public function tempFilename(string $uri = null): string;

    public function write(string $fileSystem, string $filePath, string $content, array $config = []): string;

    public function writeStream(string $fileSystem, string $filePath, $content, array $config = []): string;

    public function setVisibility(string $uri, string $visibility): void;

    public function copy(
        string $sourceUri,
        string $destinationFileSystem,
        ?string $targetFilePath = null,
        array $config = []
    ): string;

    public function move(
        string $sourceUri,
        string $destinationFileSystem,
        ?string $targetFilePath = null,
        array $config = []
    ): string;

    public function delete(string $uri): void;
}
