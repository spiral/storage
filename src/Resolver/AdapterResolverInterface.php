<?php

declare(strict_types=1);

namespace Spiral\Storage\Resolver;

use Spiral\Storage\Exception\StorageException;

interface AdapterResolverInterface
{
    /**
     * Build url by provided uri
     *
     * @param string $uri
     * @param array $options any required options can be used
     *
     * @return string|null
     *
     * @throws StorageException
     */
    public function buildUrl(string $uri, array $options = []): ?string;
}
