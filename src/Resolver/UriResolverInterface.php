<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Resolver\AdapterResolver\DTO\UriStructure;

interface UriResolverInterface
{
    public function buildUri(string $serverKey, string $filePath): string;

    public function parseUriToStructure(string $uri): UriStructure;
}
