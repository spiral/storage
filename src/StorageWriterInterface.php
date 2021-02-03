<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

interface StorageWriterInterface
{
    public function tempFilename(string $uri = null): string;

    public function write(string $server, string $filePath, string $content, array $config = []): string;

    public function writeStream(string $server, string $filePath, $content, array $config = []): string;

    public function setVisibility(string $uri, string $visibility): void;

    public function copy(string $sourceUri, string $destinationServer, ?string $targetFilePath = null, array $config = []);

    public function move(string $sourceUri, string $destinationServer, ?string $targetFilePath = null, array $config = []);

    public function delete(string $uri): void;
}
