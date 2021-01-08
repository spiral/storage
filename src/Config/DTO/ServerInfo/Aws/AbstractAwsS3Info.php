<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo\Aws;

use League\Flysystem\Visibility;
use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\SpecificConfigurableServerInfo;
use Spiral\StorageEngine\Exception\StorageException;

abstract class AbstractAwsS3Info extends ServerInfo implements SpecificConfigurableServerInfo
{
    public const BUCKET_NAME = 'bucket';
    public const CLIENT_NAME = 'client';
    public const PATH_PREFIX = 'path-prefix';

    protected const SERVER_NAME = 'aws';

    protected array $requiredOptions = [
        self::BUCKET_NAME,
        self::CLIENT_NAME,
    ];

    protected array $optionalOptions = [
        self::PATH_PREFIX,
        self::VISIBILITY,
    ];

    protected AwsClientInfo $clientInfo;

    /**
     * @param array $info
     *
     * @throws StorageException
     */
    public function constructSpecific(array $info): void
    {
        $this->clientInfo = new AwsClientInfo($this->getOption(static::CLIENT_NAME));
    }

    /**
     * @inheritDoc
     */
    public function validate(): void
    {
        if (!$this->checkRequiredOptions()) {
            if (!$this->hasOption(static::BUCKET_NAME)) {
                throw new ConfigException(
                    \sprintf('%s server needs used bucket name defined', $this->getServerName())
                );
            }

            if (!$this->hasOption(static::CLIENT_NAME)) {
                throw new ConfigException(
                    \sprintf('%s server needs S3 client description', $this->getServerName())
                );
            }

            throw new ConfigException(
                \sprintf(
                    '%s server needs all required options defined: %s',
                    $this->getServerName(),
                    implode(',', $this->requiredOptions)
                )
            );
        }

        foreach ($this->optionalOptions as $optionLabel) {
            if ($this->hasOption($optionLabel)) {
                $optionVal = $this->getOption($optionLabel);
                switch ($optionLabel) {
                    case static::VISIBILITY:
                        $allowedValues = [Visibility::PUBLIC, Visibility::PRIVATE];
                        if (!in_array($optionVal, $allowedValues, true)) {
                            throw new ConfigException(
                                \sprintf(
                                    '%s should be defined with one of values: %s',
                                    $optionLabel,
                                    implode(',', $allowedValues)
                                )
                            );
                        }
                        break;
                    case static::PATH_PREFIX:
                        if (!is_scalar($optionVal)) {
                            throw new ConfigException(
                                \sprintf('%s should be defined as scalar value', $optionLabel)
                            );
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
     * @return null
     */
    public function buildBucketPath(string $bucketName, ?string $fileName = null): ?string
    {
        return null;
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

    protected function getServerName(): string
    {
        return static::SERVER_NAME;
    }
}
