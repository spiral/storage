<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo\Aws;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\SpecificConfigurableServerInfo;
use Spiral\StorageEngine\Exception\StorageException;

class AwsS3Info extends ServerInfo implements SpecificConfigurableServerInfo
{
    public const BUCKET_NAME = 'bucket';
    public const CLIENT_NAME = 'client';
    public const PATH_PREFIX = 'path-prefix';

    protected const SERVER_INFO_TYPE = 'awsS3';

    protected const REQUIRED_OPTIONS = [
        self::BUCKET_NAME,
        self::CLIENT_NAME,
    ];

    protected const ADDITIONAL_OPTIONS= [
        self::PATH_PREFIX,
        self::VISIBILITY,
    ];

    protected AwsClientInfo $clientInfo;

    protected ?AwsVisibilityConverter $visibilityConverter = null;

    /**
     * @param array $info
     *
     * @throws StorageException
     */
    public function constructSpecific(array $info): void
    {
        $this->clientInfo = new AwsClientInfo($this->getOption(static::CLIENT_NAME));

        if ($this->hasOption(static::VISIBILITY)) {
            $this->visibilityConverter = new AwsVisibilityConverter($this->getOption(static::VISIBILITY));
        }
    }

    /**
     * @inheritDoc
     */
    public function validate(): void
    {
        if (!$this->checkRequiredOptions()) {
            if (!$this->hasOption(static::BUCKET_NAME) || !is_string($this->getOption(static::BUCKET_NAME))) {
                throw new ConfigException(
                    \sprintf('%s server needs used bucket name defined as string', $this->getServerInfoType())
                );
            }

            if (!$this->hasOption(static::CLIENT_NAME)) {
                throw new ConfigException(
                    \sprintf('%s server needs S3 client description', $this->getServerInfoType())
                );
            }

            throw new ConfigException(
                \sprintf(
                    '%s server needs all required options defined: %s',
                    $this->getServerInfoType(),
                    implode(',', static::REQUIRED_OPTIONS)
                )
            );
        }

        foreach (static::ADDITIONAL_OPTIONS as $optionLabel) {
            if ($this->hasOption($optionLabel)) {
                $optionVal = $this->getOption($optionLabel);
                switch ($optionLabel) {
                    case static::VISIBILITY:
                        if (!is_array($optionVal)) {
                            throw new ConfigException(
                                \sprintf('%s should be defined as array', $optionLabel)
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

    public function getVisibiltyConverter()
    {
        return $this->visibilityConverter instanceof AwsVisibilityConverter
            ? $this->visibilityConverter->getConverter()
            : null;
    }

    public function getClient()
    {
        return $this->clientInfo->getClient();
    }

    public function isAdvancedUsage(): bool
    {
        foreach (static::ADDITIONAL_OPTIONS as $optionalOption) {
            if ($this->hasOption($optionalOption)) {
                return true;
            }
        }

        return false;
    }
}
