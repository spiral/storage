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

    private $client;

    /**
     * @param array $clientInfo
     *
     * @throws StorageException
     */
    public function __construct(array $clientInfo)
    {
        if (
            !array_key_exists(static::CLASS_KEY, $clientInfo)
            || !is_string($clientInfo[static::CLASS_KEY])
        ) {
            throw new ConfigException('Aws client must be described with s3 client class');
        }

        if (
            !array_key_exists(static::OPTIONS_KEY, $clientInfo)
            || empty($clientInfo[static::OPTIONS_KEY])
            || !is_array($clientInfo[static::OPTIONS_KEY])
        ) {
            throw new ConfigException('Aws client must be described with s3 client options list');
        }

        $this->setClass($clientInfo[static::CLASS_KEY], $clientInfo[static::CLASS_KEY]);

        $this->options = $clientInfo[static::OPTIONS_KEY];
    }

    public function getClient()
    {
        $class = $this->getClass();

        if (!$this->client instanceof $class) {
            $this->client = new $class($this->options);
        }

        return $this->client;
    }
}
