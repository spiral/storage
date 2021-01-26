<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use League\Flysystem\FilesystemAdapter;

interface AdapterBuilderInterface
{
    /**
     * Build adapter with minimal required params
     *
     * @return FilesystemAdapter
     */
    public function buildSimple(): FilesystemAdapter;

    /**
     * Build adapter with some of additional configurable params
     *
     * @return FilesystemAdapter
     */
    public function buildAdvanced(): FilesystemAdapter;
}