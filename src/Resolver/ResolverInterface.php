<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

interface ResolverInterface
{
    public function buildUrl(string $uri, array $options = []): ?string;
}
