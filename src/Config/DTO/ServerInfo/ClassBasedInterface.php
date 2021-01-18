<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

interface ClassBasedInterface
{
    public const CLASS_KEY = 'class';

    public function getClass(): string;
}
