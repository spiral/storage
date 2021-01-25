<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo\Aws;

use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\SpecificConfigurableServerInfo;
use Spiral\StorageEngine\Exception\StorageException;

class AwsS3Info extends ServerInfo implements SpecificConfigurableServerInfo
{
    public const BUCKET = 'bucket';
    public const CLIENT = 'client';
    public const PATH_PREFIX = 'path-prefix';

    protected const SERVER_INFO_TYPE = 'awsS3';

    protected const REQUIRED_OPTIONS = [
        self::BUCKET => self::STRING_TYPE,
        self::CLIENT => self::ARRAY_TYPE,
    ];

    protected const ADDITIONAL_OPTIONS = [
        self::PATH_PREFIX => self::STRING_TYPE,
        self::VISIBILITY => self::ARRAY_TYPE,
    ];

    protected AwsClientInfo $clientInfo;

    protected ?AwsVisibilityConverter $visibilityConverter = null;

    /**
     * @param array $info
     *
     * @throws StorageException
     */
    public function constructSpecific(array $info): void
    {
        $this->clientInfo = new AwsClientInfo($this->getOption(static::CLIENT));

        if ($this->hasOption(static::VISIBILITY)) {
            $this->visibilityConverter = new AwsVisibilityConverter($this->getOption(static::VISIBILITY));
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
        return $this->clientInfo->getClient();
    }
}
