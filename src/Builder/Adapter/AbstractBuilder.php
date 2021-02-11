<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Builder\Adapter;

use Spiral\StorageEngine\Config\DTO\FileSystemInfo\FileSystemInfoInterface;
use Spiral\StorageEngine\Exception\StorageException;

abstract class AbstractBuilder implements AdapterBuilderInterface
{
    protected const FILE_SYSTEM_INFO_CLASS = '';

    protected FileSystemInfoInterface $fsInfo;

    /**
     * @param FileSystemInfoInterface $fsInfo
     * @throws StorageException
     */
    public function __construct(FileSystemInfoInterface $fsInfo)
    {
        $requiredClass = static::FILE_SYSTEM_INFO_CLASS;

        if (empty($requiredClass) || !$fsInfo instanceof $requiredClass) {
            throw new StorageException(
                \sprintf('Wrong file system info %s provided for %s', get_class($fsInfo), static::class)
            );
        }

        $this->fsInfo = $fsInfo;
    }
}
