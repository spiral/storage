<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Parser\DTO;

interface UriStructureInterface
{
    public function getServer(): string;

    public function getPath(): string;

    public function __toString(): string;
}
