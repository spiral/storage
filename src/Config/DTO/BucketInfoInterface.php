<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

interface BucketInfoInterface
{
    public const DIRECTORY_KEY = 'directory';

    public function getDirectory(): ?string;
}
