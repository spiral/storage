<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\StorageEngine\Exception\StorageException;

interface ServerInfoInterface
{
    /**
     * @throws StorageException
     */
    public function validate(): void;

    public function hasOption(string $key): bool;

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOption(string $key);

    public function getClass(): string;
}