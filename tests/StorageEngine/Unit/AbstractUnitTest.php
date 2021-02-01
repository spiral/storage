<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Resolver\FilePathResolver;
use Spiral\StorageEngine\Tests\Traits\ReflectionHelperTrait;
use Spiral\StorageEngine\Validation\FilePathValidator;

abstract class AbstractUnitTest extends TestCase
{
    use ReflectionHelperTrait;

    protected ?FilePathResolver $filePathResolver = null;
    protected ?FilePathValidator $filePathValidator = null;

    protected function getFilePathResolver(): FilePathResolver
    {
        if ($this->filePathResolver instanceof FilePathResolver) {
            return $this->filePathResolver;
        }

        return new FilePathResolver($this->getFilePathValidator());
    }

    protected function getFilePathValidator(): FilePathValidator
    {
        if ($this->filePathValidator instanceof FilePathValidator) {
            return $this->filePathValidator;
        }

        return new FilePathValidator();
    }
}
