<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Traits;

use Spiral\StorageEngine\Enum\HttpStatusCode;
use Spiral\StorageEngine\Exception\StorageException;

trait ClassBasedTrait
{
    protected string $class;

    public function getClass(): string
    {
        return $this->class;
    }

    /**
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
     * @param string $class
     * @param string $errorPostfix
     *
     * @throws StorageException
     */
    protected function checkClass(string $class, string $errorPostfix): void
    {
        if (!class_exists($class)) {
            throw new StorageException(
                \sprintf('Class %s not exists. %s', $class, $errorPostfix),
                HttpStatusCode::NOT_FOUND
            );
        }
    }
}
