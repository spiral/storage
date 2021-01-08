<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Exception\StorageException;

class LocalInfo extends ServerInfo
{
    public const ROOT_DIR_OPTION = 'rootDir';
    public const WRITE_FLAGS = 'write-flags';
    public const LINK_HANDLING = 'link-handling';
    public const HOST = 'host';

    protected array $requiredOptions = [
        self::ROOT_DIR_OPTION,
        self::HOST,
    ];

    protected array $optionalOptions = [
        self::VISIBILITY,
        self::WRITE_FLAGS,
        self::LINK_HANDLING,
    ];

    /**
     * @inheritDoc
     */
    public function validate(): void
    {
        if (!$this->checkRequiredOptions()) {
            if (!$this->hasOption(static::ROOT_DIR_OPTION)) {
                throw new ConfigException('Local server needs rootDir defined');
            }

            if (!$this->hasOption(static::HOST)) {
                throw new ConfigException('Local server needs host defined for urls providing');
            }

            throw new ConfigException(
                'Local server needs all required options defined: ' . implode(',', $this->requiredOptions)
            );
        }

        foreach ($this->optionalOptions as $optionLabel) {
            if ($this->hasOption($optionLabel)) {
                $option = $this->getOption($optionLabel);
                switch ($optionLabel) {
                    case static::VISIBILITY:
                        if (!empty($option) && !is_array($option)) {
                            throw new ConfigException('Visibility specification should be defined as array');
                        }
                        break;
                    case static::WRITE_FLAGS:
                    case static::LINK_HANDLING:
                        if (!is_numeric($option)) {
                            throw new ConfigException(\sprintf('%s should be defined as integer', $optionLabel));
                        }
                        break;
                }
            }
        }
    }

    /**
     * @param string $bucketName
     * @param string|null $fileName
     *
     * @return string
     *
     * @throws StorageException
     */
    public function buildBucketPath(string $bucketName, ?string $fileName = null): string
    {
        $bucket = $this->getBucket($bucketName);

        if (!$bucket instanceof BucketInfo) {
            throw new StorageException(
                \sprintf('Bucket %s is not defined', $bucketName)
            );
        }

        return $this->getOption(static::ROOT_DIR_OPTION) . $bucket->getDirectory() . $fileName;
    }

    public function isAdvancedUsage(): bool
    {
        foreach ($this->optionalOptions as $optionalOption) {
            if ($this->hasOption($optionalOption)) {
                return true;
            }
        }

        return false;
    }
}
