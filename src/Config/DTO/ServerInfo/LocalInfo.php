<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\StorageEngine\Config\DTO\Traits\BucketsTrait;

class LocalInfo extends ServerInfo implements BucketsBasedInterface
{
    use BucketsTrait;

    public const ROOT_DIR_KEY = 'rootDir';
    public const WRITE_FLAGS_KEY = 'write-flags';
    public const LINK_HANDLING_KEY = 'link-handling';
    public const HOST_KEY = 'host';

    protected const SERVER_INFO_TYPE = 'local';

    protected const REQUIRED_OPTIONS = [
        self::ROOT_DIR_KEY => self::STRING_TYPE,
    ];

    protected const ADDITIONAL_OPTIONS = [
        self::VISIBILITY_KEY => self::ARRAY_TYPE,
        self::WRITE_FLAGS_KEY => self::INT_TYPE,
        self::LINK_HANDLING_KEY => self::INT_TYPE,
        self::HOST_KEY => self::STRING_TYPE,
    ];

    public function __construct(string $name, array $info)
    {
        parent::__construct($name, $info);

        if (array_key_exists(BucketsBasedInterface::BUCKETS_KEY, $info)) {
            $this->constructBuckets($info[static::BUCKETS_KEY], $this);
        }
    }

    public function isAdvancedUsage(): bool
    {
        foreach (static::ADDITIONAL_OPTIONS as $optionalOption => $type) {
            if ($optionalOption === static::HOST_KEY) {
                continue;
            }

            if ($this->hasOption($optionalOption)) {
                return true;
            }
        }

        return false;
    }
}
