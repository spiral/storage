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
        $this->name = $name;

        $this->constructClass($info);

        $this->constructOptions($info);

        $this->constructBuckets($info);

        $this->validate();
    }

    /**
     * @param array $info
     *
     * @throws StorageException
     */
    protected function constructClass(array $info): void
    {
        if (!array_key_exists(static::CLASS_KEY, $info)) {
            throw new ConfigException(
                \sprintf('Server %s needs adapter class defined', $this->name)
            );
        }

        $this->checkClass($info[static::CLASS_KEY], \sprintf('Server %s class', $this->name));

        $this->class = $info[static::CLASS_KEY];
    }

    protected function constructOptions(array $info): void
    {
        if (array_key_exists(static::OPTIONS_KEY, $info)) {
            $this->options = $info[static::OPTIONS_KEY];
        }
    }

    protected function constructBuckets(array $info): void
    {
        if (array_key_exists(static::BUCKETS_KEY, $info)) {
            foreach ($info[static::BUCKETS_KEY] as $bucketName => $bucketInfo) {
                $this->buckets[] = new BucketInfo($bucketName, $this->name, $bucketInfo);
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

    /**
     * @throws StorageException
     */
    abstract protected function validate(): void;
}
