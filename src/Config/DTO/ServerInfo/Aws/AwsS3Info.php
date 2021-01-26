<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config\DTO\ServerInfo\Aws;

use Spiral\Core\Exception\ConfigException;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\SpecificConfigurableServerInfo;
use Spiral\StorageEngine\Exception\StorageException;

class AwsS3Info extends ServerInfo implements SpecificConfigurableServerInfo
{
    public const BUCKET = 'bucket';
    public const CLIENT = 'client';
    public const PATH_PREFIX = 'path-prefix';
    public const URL_EXPIRES = 'url-expires';

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
        $this->clientInfo = new AwsClientInfo($this->getOption(static::CLIENT));

        if ($this->hasOption(static::VISIBILITY)) {
            $this->visibilityConverter = new AwsVisibilityConverter($this->getOption(static::VISIBILITY));
        }

        if (
            array_key_exists(static::OPTIONS_KEY, $info)
            && array_key_exists(static::URL_EXPIRES, $info[static::OPTIONS_KEY])
        ) {
            $this->setUrlExpires($info[static::OPTIONS_KEY][static::URL_EXPIRES]);
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

    /**
     * @param string|\DateTimeInterface $expires
     *
     * @return $this
     */
    public function setUrlExpires($expires): self
    {
        if (empty($expires) || (!is_string($expires) && !$expires instanceof \DateTimeInterface)) {
            throw new ConfigException(
                'Url expires should be string or DateTimeInterface implemented object for server '
                . $this->getName()
            );
        }

        $this->urlExpires = $expires;

        return $this;
    }

    /**
     * @return \DateTimeInterface|string
     */
    public function getUrlExpires()
    {
        return $this->urlExpires;
    }
}
