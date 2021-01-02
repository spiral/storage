<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Traits;

use Spiral\StorageEngine\Enum\HttpStatusCode;
use Spiral\StorageEngine\Exception\StorageException;

trait ClassBasedTrait
{
    public string $class;

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @param string $errorPostfix
     *
     * @throws StorageException
     */
    public function checkClass(string $class, string $errorPostfix): void
    {
        if (!class_exists($class)) {
            throw new StorageException(
                \sprintf('Class %s not exists. %s', $class, $errorPostfix),
                HttpStatusCode::NOT_FOUND
            );
        }
    }
}
