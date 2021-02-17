<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\Traits;

use Spiral\StorageEngine\Exception\StorageException;

/**
 * Trait for dto based on class usage
 */
trait ClassBasedTrait
{
    protected ?string $class = null;

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set class for DTO and check if class exists
     *
     * @param string $class
     * @param string|null $exceptionMsg
     *
     * @return static
     *
     * @throws StorageException
     */
    public function setClass(string $class, ?string $exceptionMsg = null): self
    {
        $this->checkClass($class, $exceptionMsg ?: '');

        $this->class = $class;

        return $this;
    }

    /**
     * Check if class exists
     *
     * @param string $class
     * @param string $errorPostfix
     *
     * @throws StorageException
     */
    protected function checkClass(string $class, string $errorPostfix): void
    {
        if (!class_exists($class)) {
            throw new StorageException(
                \sprintf('Class `%s` not exists. %s', $class, $errorPostfix)
            );
        }
    }
}
