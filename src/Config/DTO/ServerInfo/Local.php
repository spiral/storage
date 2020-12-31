<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\Core\Exception\ConfigException;

class Local extends ServerInfo
{
    protected const ROOT_DIR_OPTION = 'rootDir';
    protected const VISIBILITY = 'visibility';
    protected const WRITE_FLAGS = 'write-flags';
    protected const LINK_HANDLING = 'link-handling';

    protected array $requiredOptions = [
        self::CLASS_KEY,
        self::ROOT_DIR_OPTION,
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
            if ($this->hasOption(static::ROOT_DIR_OPTION)) {
                throw new ConfigException('Local server needs rootDir defined');
            }

            throw new ConfigException(
                'Local server needs all required options defined: ' . implode(',', $this->requiredOptions)
            );
        }

        foreach ($this->optionalOptions as $optionLabel) {
            if ($this->hasOption($optionLabel)) {
                $option = $this->getOption($optionLabel);
                switch ($optionLabel) {
                    case self::VISIBILITY:
                        if (!empty($option) && !is_array($option)) {
                            throw new ConfigException('Visibility specification should be defined as array');
                        }
                        break;
                    case self::WRITE_FLAGS:
                    case self::LINK_HANDLING:
                        if (!is_numeric($option)) {
                            throw new ConfigException($optionLabel . ' should be defined as integer');
                        }
                        break;
                }
            }
        }
    }
}