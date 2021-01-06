<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Interfaces;

interface ServerTestInterface
{
    public const LOCAL_SERVER_NAME = 'debugLocalServer';
    public const VFS_PREFIX = 'vfs://';
    public const ROOT_DIR_NAME = 'testRoot';
    public const ROOT_DIR = '/' . self::ROOT_DIR_NAME;
    public const CONFIG_HOST = 'http://localhost/debug/';
}
