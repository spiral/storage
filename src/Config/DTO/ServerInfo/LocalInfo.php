<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo;

use Spiral\StorageEngine\Config\DTO\Traits\BucketsTrait;

class LocalInfo extends ServerInfo implements BucketsBasedInterface
{
    use BucketsTrait;

    public const ROOT_DIR = 'rootDir';
    public const WRITE_FLAGS = 'write-flags';
    public const LINK_HANDLING = 'link-handling';
    public const HOST = 'host';

    protected const SERVER_INFO_TYPE = 'local';

    protected const REQUIRED_OPTIONS = [
        self::ROOT_DIR => self::STRING_TYPE,
        self::HOST => self::STRING_TYPE,
    ];

    protected const ADDITIONAL_OPTIONS = [
        self::VISIBILITY => self::ARRAY_TYPE,
        self::WRITE_FLAGS => self::INT_TYPE,
        self::LINK_HANDLING => self::INT_TYPE,
    ];

    public function __construct(string $name, array $info)
    {
        parent::__construct($name, $info);

        if (array_key_exists(BucketsBasedInterface::BUCKETS_KEY, $info)) {
            $this->constructBuckets($info[static::BUCKETS_KEY], $this);
        }
    }
}
