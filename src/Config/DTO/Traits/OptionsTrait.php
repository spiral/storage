<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\Traits;

trait OptionsTrait
{
    protected array $options = [];

    public function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOption(string $key)
    {
        return $this->hasOption($key) ? $this->options[$key] : null;
    }
}
