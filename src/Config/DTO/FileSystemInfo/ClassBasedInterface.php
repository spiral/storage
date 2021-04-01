<?php

declare(strict_types=1);

namespace Spiral\Storage\Config\DTO\FileSystemInfo;

interface ClassBasedInterface
{
    public const CLASS_KEY = 'class';

    public function getClass(): string;
}
