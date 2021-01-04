<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;

interface AdapterBuilderInterface
{
    public function buildSimple(): FilesystemAdapter;

    public function buildAdvanced(): FilesystemAdapter;
}
