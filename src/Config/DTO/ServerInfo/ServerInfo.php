<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\Traits\OptionsTrait;
use Spiral\StorageEngine\Enum\AdapterName;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Traits\ClassBasedTrait;

abstract class ServerInfo implements ServerInfoInterface, ClassBasedInterface, OptionsBasedInterface
{
    use ClassBasedTrait;
    use OptionsTrait;

    protected const REQUIRED_OPTIONS = [];

    protected const ADDITIONAL_OPTIONS = [];

    protected const SERVER_INFO_TYPE = '';

    protected string $name;

    protected string $driver;

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

        if (array_key_exists(OptionsBasedInterface::OPTIONS_KEY, $info)) {
            $this->prepareOptions($info[OptionsBasedInterface::OPTIONS_KEY]);
        }

        $this->validate();

        if ($this instanceof SpecificConfigurableServerInfo) {
            $this->constructSpecific($info);
        }
    }

    protected function prepareOptions(array $options): void
    {
        foreach ($options as $optionKey => $option) {
            if (!$this->isAvailableOption($optionKey)) {
                continue;
            }

            $this->options[$optionKey] = $option;
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

    protected function checkRequiredOptions(): bool
    {
        foreach (static::REQUIRED_OPTIONS as $requiredOption) {
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
        if (in_array($option, static::REQUIRED_OPTIONS, true)) {
            return true;
        }

        if (in_array($option, static::ADDITIONAL_OPTIONS, true)) {
            return true;
        }

        return false;
    }

    /**
     * @throws StorageException
     */
    abstract protected function validate(): void;
}
