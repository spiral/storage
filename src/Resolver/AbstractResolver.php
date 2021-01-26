<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;
use Spiral\StorageEngine\Validation\FilePathValidator;

abstract class AbstractResolver implements ResolverInterface
{
    protected const SERVER_INFO_CLASS = '';

    protected ServerInfoInterface $serverInfo;

    /**
     * @param ServerInfoInterface $serverInfo
     *
     * @throws StorageException
     */
    public function __construct(ServerInfoInterface $serverInfo)
    {
        $requiredClass = static::SERVER_INFO_CLASS;

        if (empty($requiredClass) || !$serverInfo instanceof $requiredClass) {
            throw new StorageException(
                \sprintf(
                    'Wrong server info (%s) for resolver %s',
                    get_class($serverInfo),
                    static::class
                )
            );
        }

        $this->serverInfo = $serverInfo;
    }

    public function normalizePathForServer(string $filePath): string
    {
        try {
            if (FilePathValidator::validateServerFilePath($filePath)) {
                $filePathStructure = new ServerFilePathStructure($filePath);

                return $filePathStructure->isIdentified() ? $filePathStructure->filePath : $filePath;
            }
        } catch (ValidationException $e) {
        }

        return $filePath;
    }
}
