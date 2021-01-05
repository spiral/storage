<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

interface ResolverInterface
{
    /**
     * @param string[] $files
     * @return array
     */
    public function buildUrlsList(array $files): array;
}
