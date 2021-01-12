<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\Core\Exception\ConfigException;

class LocalInfo extends ServerInfo
{
    public const ROOT_DIR_OPTION = 'rootDir';
    public const WRITE_FLAGS = 'write-flags';
    public const LINK_HANDLING = 'link-handling';
    public const HOST = 'host';

    protected const SERVER_INFO_TYPE = 'local';

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
                throw new ConfigException(\sprintf('%s server needs rootDir defined', $this->getServerInfoType()));
            }

            if (!$this->hasOption(static::HOST)) {
                throw new ConfigException(
                    \sprintf('%s server needs host defined for urls providing', $this->getServerInfoType())
                );
            }

            throw new ConfigException(
                \sprintf(
                    '%s server needs all required options defined: %s',
                    $this->getServerInfoType(),
                    implode(',', $this->requiredOptions)
                )
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
