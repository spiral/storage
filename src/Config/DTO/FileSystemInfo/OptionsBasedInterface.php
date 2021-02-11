<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\FileSystemInfo;

interface OptionsBasedInterface
{
    public const OPTIONS_KEY = 'options';

    public const INT_TYPE = 'int';
    public const FLOAT_TYPE = 'float';
    public const STRING_TYPE = 'string';
    public const BOOL_TYPE = 'bool';
    public const ARRAY_TYPE = 'array';
    public const MIXED_TYPE = 'mixed';

    public function hasOption(string $key): bool;

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOption(string $key);
}
