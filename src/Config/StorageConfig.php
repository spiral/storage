<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config;

use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\Core\InjectableConfig;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;
use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\LocalInfo;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;
use Spiral\StorageEngine\Exception\StorageException;

class StorageConfig extends InjectableConfig
{
    public const CONFIG = 'storage';

    private const SERVERS_KEY = 'servers';
    private const BUCKETS_KEY = 'buckets';
    private const TMP_DIR_KEY = 'tmp-dir';

    protected $config = [
        self::SERVERS_KEY   => [],
        self::BUCKETS_KEY => [],
        self::TMP_DIR_KEY => '',
    ];

    /**
     * @var ServerInfoInterface[]
     *
     * Internal list allows to keep once built servers info
     */
    protected array $serversInfo = [];

    /**
     * @var BucketInfoInterface[]
     *
     * Internal list allows to keep once built buckets info
     */
    protected array $bucketsInfo = [];

    public function __construct(array $config = [])
    {
        if (!array_key_exists(static::SERVERS_KEY, $config)) {
            throw new ConfigException('Servers must be defined for storage work');
        }

        if (array_key_exists(static::TMP_DIR_KEY, $config) && !is_dir($config[static::TMP_DIR_KEY])) {
            throw new ConfigException(
                \sprintf('Defined tmp directory `%s` was not detected', $config[static::TMP_DIR_KEY])
            );
        }

        parent::__construct($config);
    }

    public function getServersKeys(): array
    {
        return array_key_exists(static::SERVERS_KEY, $this->config)
            ? array_keys($this->config[static::SERVERS_KEY])
            : [];
    }

    public function hasServer(string $key): bool
    {
        return array_key_exists($key, $this->config[static::SERVERS_KEY]);
    }

    public function getBucketsKeys(): array
    {
        return array_key_exists(static::BUCKETS_KEY, $this->config)
            ? array_keys($this->config[static::BUCKETS_KEY])
            : [];
    }

    public function hasBucket(string $key): bool
    {
        return array_key_exists($key, $this->config[static::BUCKETS_KEY]);
    }

    public function getTmpDir(): string
    {
        return array_key_exists(static::TMP_DIR_KEY, $this->config)
            ? $this->config[static::TMP_DIR_KEY]
            : sys_get_temp_dir();
    }

    /**
     * @param string $serverKey
     *
     * @return array
     *
     * @throws StorageException
     */
    public function getServerBuckets(string $serverKey): array
    {
        if (!array_key_exists(static::BUCKETS_KEY, $this->config)) {
            return [];
        }

        foreach ($this->config[static::BUCKETS_KEY] as $bucketKey => $bucketDesc) {
            if ($bucketDesc[BucketInfoInterface::SERVER_KEY] !== $serverKey) {
                continue;
            }

            $this->buildBucketInfo($bucketKey);
        }

        return array_filter(
            $this->bucketsInfo,
            static fn (BucketInfoInterface $bucketInfo) => $bucketInfo->getServerKey()
        );
    }

    /**
     * Build server info by provided label
     * Force mode allows to rebuild server info for internal servers info list
     *
     * @param string $serverKey
     * @param bool|null $force
     *
     * @return ServerInfoInterface
     *
     * @throws StorageException
     */
    public function buildServerInfo(string $serverKey, ?bool $force = false): ServerInfoInterface
    {
        if (!$this->hasServer($serverKey)) {
            throw new ConfigException(
                \sprintf('Server %s was not found', $serverKey)
            );
        }

        if (!$force && array_key_exists($serverKey, $this->serversInfo)) {
            return $this->serversInfo[$serverKey];
        }

        $serverInfo = $this->config[static::SERVERS_KEY][$serverKey];

        switch ($this->extractServerAdapter($serverInfo)) {
            case \League\Flysystem\Local\LocalFilesystemAdapter::class:
                $serverInfoDTO = new LocalInfo($serverKey, $serverInfo);
                break;
            case \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class:
            case \League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter::class:
                $serverInfoDTO = new AwsS3Info($serverKey, $serverInfo);
                break;
            default:
                throw new ConfigException('Adapter can\'t be identified for server ' . $serverKey);
        }

        $this->serversInfo[$serverKey] = $serverInfoDTO;

        return $this->serversInfo[$serverKey];
    }

    /**
     * Build bucket info by provided label
     * Force mode allows to rebuild bucket info for internal list
     *
     * @param string $bucketLabel
     * @param bool|null $force
     *
     * @return BucketInfoInterface
     *
     * @throws StorageException
     */
    public function buildBucketInfo(string $bucketLabel, ?bool $force = false): BucketInfoInterface
    {
        if (!$this->hasBucket($bucketLabel)) {
            throw new StorageException(
                \sprintf(
                    'Bucket %s was not found',
                    $bucketLabel
                )
            );
        }

        if (!$force && array_key_exists($bucketLabel, $this->bucketsInfo)) {
            return $this->bucketsInfo[$bucketLabel];
        }

        $bucketInfo = $this->config[static::BUCKETS_KEY][$bucketLabel];

        $this->bucketsInfo[$bucketLabel] = new BucketInfo(
            $bucketLabel,
            $this->buildServerInfo($bucketInfo[BucketInfoInterface::SERVER_KEY]),
            $bucketInfo
        );

        return $this->bucketsInfo[$bucketLabel];
    }

    private function extractServerAdapter(array $serverInfo): ?string
    {
        return array_key_exists(ServerInfoInterface::ADAPTER_KEY, $serverInfo)
            ? $serverInfo[ServerInfoInterface::ADAPTER_KEY]
            : null;
    }
}
