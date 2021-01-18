<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

interface OptionsBasedInterface
{
    public const OPTIONS_KEY = 'options';

    public function hasOption(string $key): bool;

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOption(string $key);
}
