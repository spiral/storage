<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\Traits\OptionsTrait;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Config\DTO\Traits\ClassBasedTrait;

abstract class ServerInfo implements ServerInfoInterface, ClassBasedInterface, OptionsBasedInterface
{
    use ClassBasedTrait;
    use OptionsTrait;

    protected const REQUIRED_OPTIONS = [];

    protected const ADDITIONAL_OPTIONS = [];

    protected const SERVER_INFO_TYPE = '';

    protected string $name;

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

        $this->setClass($info[static::ADAPTER_KEY], \sprintf('Server %s class', $this->name));

        $this->prepareOptions($info[OptionsBasedInterface::OPTIONS_KEY]);

        if ($this instanceof SpecificConfigurableServerInfo) {
            $this->constructSpecific($info);
        }
    }

    protected function prepareOptions(array $options): void
    {
        $this->validateRequiredOptions(
            array_keys(static::REQUIRED_OPTIONS),
            $options,
            ' for server ' . $this->getName()
        );

        foreach ($options as $optionKey => $option) {
            if (($type = $this->getOptionType($optionKey)) === null) {
                continue;
            }

            $this->validateOptionByType($optionKey, $type, $option);

            $this->options[$optionKey] = $this->processOptionByType($option, $type);
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

    public function isAdvancedUsage(): bool
    {
        foreach (static::ADDITIONAL_OPTIONS as $optionalOption => $type) {
            if ($this->hasOption($optionalOption)) {
                return true;
            }
        }

        return false;
    }

    protected function validateInfoSufficient(string $serverName, array $info): void
    {
        if (!array_key_exists(static::ADAPTER_KEY, $info)) {
            throw new ConfigException(
                \sprintf('Server %s needs adapter class defined', $serverName)
            );
        }

        if (!array_key_exists(OptionsBasedInterface::OPTIONS_KEY, $info)) {
            throw new ConfigException(
                \sprintf('Server %s needs options defined', $serverName)
            );
        }
    }

    /**
     * @param string $optionLabel
     * @param string $optionType
     * @param mixed $optionVal
     */
    protected function validateOptionByType(string $optionLabel, string $optionType, $optionVal): void
    {
        if (!$this->isOptionHasRequiredType($optionLabel, $optionVal, $optionType)) {
            throw new ConfigException(
                \sprintf(
                    'Option %s defined in wrong format for server %s, %s expected',
                    $optionLabel,
                    $this->getName(),
                    $optionType
                )
            );
        }
    }

    protected function getOptionType(string $option): ?string
    {
        if (array_key_exists($option, static::REQUIRED_OPTIONS)) {
            return static::REQUIRED_OPTIONS[$option];
        }

        if (array_key_exists($option, static::ADDITIONAL_OPTIONS)) {
            return static::ADDITIONAL_OPTIONS[$option];
        }

        return null;
    }
}
