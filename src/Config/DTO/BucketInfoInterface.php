<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO;

interface BucketInfoInterface
{
    public const DIRECTORY_KEY = 'directory';
    public const SERVER_KEY = 'server';

    public function getDirectory(): ?string;

    public function getServerKey(): string;
}
