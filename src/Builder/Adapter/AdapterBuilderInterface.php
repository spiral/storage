<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;

interface AdapterBuilderInterface
{
    public function buildSimple(): FilesystemAdapter;

    public function buildAdvanced(): FilesystemAdapter;
}