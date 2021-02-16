<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\FileSystemInfo;

interface ClassBasedInterface
{
    public const CLASS_KEY = 'class';

    public function getClass(): string;
}
