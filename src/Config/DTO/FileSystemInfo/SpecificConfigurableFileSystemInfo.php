<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\FileSystemInfo;

interface SpecificConfigurableFileSystemInfo
{
    public function constructSpecific(array $info): void;
}
