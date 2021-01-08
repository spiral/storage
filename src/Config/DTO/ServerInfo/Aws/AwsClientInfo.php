<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo\Aws;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ClassBasedInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\OptionsBasedInterface;
use Spiral\StorageEngine\Config\DTO\Traits\OptionsTrait;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Traits\ClassBasedTrait;

class AwsClientInfo implements ClassBasedInterface, OptionsBasedInterface
{
    use ClassBasedTrait;
    use OptionsTrait;

    /**
     * @param array $clientInfo
     *
     * @throws StorageException
     */
    public function __construct(array $clientInfo)
    {
        if (!array_key_exists(self::CLASS_KEY, $clientInfo)) {
            throw new ConfigException('Aws client must be described with s3 client class');
        }

        if (!array_key_exists(self::OPTIONS_KEY, $clientInfo) || empty($clientInfo[static::OPTIONS_KEY])) {
            throw new ConfigException('Aws client must be described with s3 client options');
        }

        $this->setClass($clientInfo[static::CLASS_KEY], $clientInfo[static::CLASS_KEY]);

        $this->options = $clientInfo[self::OPTIONS_KEY];
    }
}
