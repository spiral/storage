<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;

interface FilePathResolverInterface
{
    public function buildServerFilePath(string $serverKey, string $filePath): string;

    public function parseServerFilePathToStructure(string $filePath): ?ServerFilePathStructure;
}
