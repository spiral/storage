<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

interface BucketInfoInterface
{
    public function getDirectory(): ?string;
}
