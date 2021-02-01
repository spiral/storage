<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemOperator;

interface StorageInterface extends StorageReaderInterface, StorageWriterInterface
{
    public function getMountManager(): ?FilesystemOperator;
}
