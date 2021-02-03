<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

interface StorageReaderInterface
{
    public function fileExists(string $uri): bool;
}
