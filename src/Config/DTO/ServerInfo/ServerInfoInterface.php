<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

interface ServerInfoInterface
{
    public function hasOption(string $key): bool;

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOption(string $key);

    public function getClass(): string;

    public function isAdvancedUsage(): bool;
}
