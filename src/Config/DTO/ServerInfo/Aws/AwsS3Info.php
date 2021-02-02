<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo\Aws;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\SpecificConfigurableServerInfo;
use Spiral\StorageEngine\Exception\StorageException;

class AwsS3Info extends ServerInfo implements SpecificConfigurableServerInfo
{
    public const BUCKET_KEY = 'bucket';
    public const CLIENT_KEY = 'client';
    public const PATH_PREFIX_KEY = 'path-prefix';

    protected const SERVER_INFO_TYPE = 'awsS3';

    protected const REQUIRED_OPTIONS = [
        self::BUCKET_KEY => self::STRING_TYPE,
        self::CLIENT_KEY => self::MIXED_TYPE,
    ];

    protected const ADDITIONAL_OPTIONS = [
        self::PATH_PREFIX_KEY => self::STRING_TYPE,
        self::VISIBILITY_KEY => self::ARRAY_TYPE,
    ];

    protected ?AwsVisibilityConverter $visibilityConverter = null;

    /**
     * @var string|\DateTimeInterface
     */
    protected $urlExpires = '+24hours';

    /**
     * @param array $info
     *
     * @throws StorageException
     */
    public function constructSpecific(array $info): void
    {
        if ($this->hasOption(static::VISIBILITY_KEY)) {
            $this->visibilityConverter = new AwsVisibilityConverter($this->getOption(static::VISIBILITY_KEY));
        }
    }

    public function getVisibiltyConverter()
    {
        return $this->visibilityConverter instanceof AwsVisibilityConverter
            ? $this->visibilityConverter->getConverter()
            : null;
    }

    public function getClient()
    {
        return $this->getOption(static::CLIENT_KEY);
    }
}
