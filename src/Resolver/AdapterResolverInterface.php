<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

interface AdapterResolverInterface
{
    public function buildUrl(string $uri, array $options = []): ?string;
}
