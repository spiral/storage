<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\Traits\OptionsTrait;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Traits\ClassBasedTrait;

abstract class ServerInfo implements ServerInfoInterface
{
    use ClassBasedTrait;
    use OptionsTrait;
    
    protected const OPTIONS_KEY = 'options';
    protected const BUCKETS_KEY = 'buckets';

    protected const CLASS_KEY = 'class';

    public string $name;

    public array $buckets = [];

    protected array $requiredOptions = [self::CLASS_KEY];

    protected array $optionalOptions = [];

    /**
     * @param string $name
     * @param array $info
     *
     * @throws StorageException
     */
    public function __construct(string $name, array $info)
    {
        $this->name = $name;

        if (!array_key_exists(self::CLASS_KEY, $info)) {
            throw new ConfigException(
                \sprintf('Server %s needs adapter class defined', $name)
            );
        }

        $this->checkClass($info[self::CLASS_KEY], \sprintf('Server %s class', $name));

        if (array_key_exists(static::OPTIONS_KEY, $info)) {
            $this->options = $info[static::OPTIONS_KEY];
        }

        if (array_key_exists(static::BUCKETS_KEY, $info)) {
            foreach ($info[self::BUCKETS_KEY] as $bucketName => $bucketInfo) {
                $this->buckets[] = new BucketInfo($bucketName, $name, $bucketInfo);
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
}
