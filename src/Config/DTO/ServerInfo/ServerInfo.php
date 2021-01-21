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

        $this->prepareOptions($info[OptionsBasedInterface::OPTIONS_KEY]);

        if ($this instanceof SpecificConfigurableServerInfo) {
            $this->constructSpecific($info);
        }
    }

    protected function prepareOptions(array $options): void
    {
        $this->validateRequiredOptions($options);

        foreach ($options as $optionKey => $option) {
            if (($type = $this->getOptionType($optionKey)) === null) {
                continue;
            }

            $this->validateOptionByType($optionKey, $type, $option);

            switch ($type) {
                case static::INT_TYPE:
                    $option = (int) $option;
                    break;
                case static::FLOAT_TYPE:
                    $option = (float) $option;
                    break;
                case static::BOOL_TYPE:
                    $option = (bool) $option;
                    break;
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
        switch ($optionType) {
            case static::INT_TYPE:
                $correctType = is_numeric($optionVal);
                break;
            case static::STRING_TYPE:
                $correctType = is_string($optionVal);
                break;
            case static::ARRAY_TYPE:
                $correctType = is_array($optionVal);
                break;
            case static::MIXED_TYPE:
                $correctType = true;
                break;
            default:
                throw new ConfigException(
                    \sprintf(
                        'Unknown option type detected for server %s option %s: %s',
                        $this->getName(),
                        $optionLabel,
                        $optionType
                    )
                );
        }

        if (!$correctType) {
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

    /**
     * @param array $options
     *
     * @return bool
     *
     * @throws ConfigException
     */
    protected function validateRequiredOptions(array $options): bool
    {
        foreach (static::REQUIRED_OPTIONS as $requiredOption => $requiredType) {
            if (!array_key_exists($requiredOption, $options)) {
                throw new ConfigException(
                    \sprintf('Option %s not detected for server %s', $requiredOption, $this->getName())
                );
            }
        }

        return true;
    }
}
