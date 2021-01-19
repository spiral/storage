<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\Traits\BucketsTrait;
use Spiral\StorageEngine\Config\DTO\Traits\OptionsTrait;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Traits\ClassBasedTrait;

abstract class ServerInfo implements ServerInfoInterface, ClassBasedInterface, OptionsBasedInterface
{
    use BucketsTrait;
    use ClassBasedTrait;
    use OptionsTrait;

    public const VISIBILITY = 'visibility';

    protected const SERVER_INFO_TYPE = '';

    protected string $name;

    protected string $driver;

    protected array $requiredOptions = [];

    protected array $optionalOptions = [];

    /**
     * @param string $name
     * @param array $info
     *
     * @throws StorageException
     */
    public function __construct(string $name, array $info)
    {
        $this->validateInfoSufficient($name, $info);

        $this->name = $name;

        $this->driver = $info[static::DRIVER_KEY];

        $this->setClass($info[static::CLASS_KEY], \sprintf('Server %s class', $this->name));

        if (array_key_exists(static::OPTIONS_KEY, $info)) {
            foreach ($info[static::OPTIONS_KEY] as $optionKey => $option) {
                if (!$this->isAvailableOption($optionKey)) {
                    continue;
                }

                $this->options[$optionKey] = $option;
            }
        }

        $this->constructBuckets($info);

        $this->validate();

        if ($this instanceof SpecificConfigurableServerInfo) {
            $this->constructSpecific($info);
        }
    }

    public function getAdapterClass(): string
    {
        return $this->getClass();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    protected function validateInfoSufficient(string $serverName, array $info): void
    {
        if (
            !array_key_exists(static::DRIVER_KEY, $info)
            || !in_array($info[static::DRIVER_KEY], AdapterName::ALL, true)
        ) {
            throw new ConfigException(
                \sprintf('Server driver for %s was not identified', $serverName)
            );
        }

        if (!array_key_exists(static::CLASS_KEY, $info)) {
            throw new ConfigException(
                \sprintf('Server %s needs adapter class defined', $serverName)
            );
        }
    }

    protected function constructBuckets(array $info): void
    {
        if (array_key_exists(static::BUCKETS_KEY, $info)) {
            foreach ($info[static::BUCKETS_KEY] as $bucketName => $bucketInfo) {
                $this->addBucket(new BucketInfo($bucketName, $this, $bucketInfo));
            }
        }
    }

    protected function checkRequiredOptions(): bool
    {
        foreach ($this->requiredOptions as $requiredOption) {
            if (!$this->hasOption($requiredOption)) {
                return false;
            }
        }

        return true;
    }

    protected function getServerInfoType(): string
    {
        return static::SERVER_INFO_TYPE;
    }

    protected function isAvailableOption(string $option): bool
    {
        if (in_array($option, $this->requiredOptions, true)) {
            return true;
        }

        if (in_array($option, $this->optionalOptions, true)) {
            return true;
        }

        return false;
    }

    /**
     * @throws StorageException
     */
    abstract protected function validate(): void;
}
