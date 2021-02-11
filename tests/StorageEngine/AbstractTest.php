<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\StorageEngine\Parser\UriParser;
use Spiral\StorageEngine\Parser\UriParserInterface;

class AbstractTest extends TestCase
{
    protected ?UriParserInterface $uriParser = null;

    protected function getUriParser(): UriParserInterface
    {
        if (!$this->uriParser instanceof UriParserInterface) {
            $this->uriParser = new UriParser();
        }

        return $this->uriParser;
    }
}
