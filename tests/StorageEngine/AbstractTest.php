<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Resolver\UriResolver;
use Spiral\StorageEngine\Validation\FilePathValidator;

class AbstractTest extends TestCase
{
    protected ?UriResolver $uriResolver = null;
    protected ?FilePathValidator $filePathValidator = null;

    protected function getUriResolver(): UriResolver
    {
        if ($this->uriResolver instanceof UriResolver) {
            return $this->uriResolver;
        }

        return new UriResolver($this->getFilePathValidator());
    }

    protected function getFilePathValidator(): FilePathValidator
    {
        if ($this->filePathValidator instanceof FilePathValidator) {
            return $this->filePathValidator;
        }

        return new FilePathValidator();
    }
}
