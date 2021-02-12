<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\Traits;

use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo\OptionsBasedInterface;

trait OptionsTrait
{
    protected array $options = [];

    public function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    public function getOption(string $key)
    {
        return $this->hasOption($key) ? $this->options[$key] : null;
    }

    /**
     * @param string $optionLabel
     * @param $optionVal
     * @param string $type
     *
     * @return bool
     *
     * @throws ConfigException
     */
    protected function isOptionHasRequiredType(string $optionLabel, $optionVal, string $type): bool
    {
        switch ($type) {
            case OptionsBasedInterface::INT_TYPE:
            case OptionsBasedInterface::FLOAT_TYPE:
                return is_numeric($optionVal);
            case OptionsBasedInterface::STRING_TYPE:
                return is_string($optionVal);
            case OptionsBasedInterface::BOOL_TYPE:
                return is_bool($optionVal) || in_array($optionVal, [0, 1, '0', '1'], true);
            case OptionsBasedInterface::ARRAY_TYPE:
                return is_array($optionVal);
            case OptionsBasedInterface::MIXED_TYPE:
                return true;
            default:
                throw new ConfigException(
                    \sprintf(
                        'Unknown option type detected for option `%s`: %s',
                        $optionLabel,
                        $type
                    )
                );
        }
    }

    protected function processOptionByType($optionVal, string $type)
    {
        switch ($type) {
            case OptionsBasedInterface::INT_TYPE:
                return (int)$optionVal;
            case OptionsBasedInterface::FLOAT_TYPE:
                return (float)$optionVal;
            case OptionsBasedInterface::STRING_TYPE:
                return (string)$optionVal;
            case OptionsBasedInterface::BOOL_TYPE:
                return (bool)$optionVal;
        }

        return $optionVal;
    }

    /**
     * @param array $requiredOptions
     * @param array $options
     * @param string $msgPostfix
     *
     * @throws ConfigException
     */
    protected function validateRequiredOptions(array $requiredOptions, array $options, string $msgPostfix = ''): void
    {
        foreach ($requiredOptions as $requiredOption) {
            if (!array_key_exists($requiredOption, $options)) {
                throw new ConfigException(
                    \sprintf('Option `%s` not detected%s', $requiredOption, $msgPostfix)
                );
            }
        }
    }
}
