<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Config;

use Spiral\StorageEngine\Exception\ConfigException;
use Spiral\Core\InjectableConfig;
use Spiral\StorageEngine\Config\DTO\BucketInfo;
use Spiral\StorageEngine\Config\DTO\BucketInfoInterface;
use Spiral\StorageEngine\Config\DTO\FileSystemInfo;
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
     * @var FileSystemInfo\FileSystemInfoInterface[]
     *
     * Internal list allows to keep once built file systems info
     */
    protected array $fileSystemsInfoList = [];

    /**
     * @var BucketInfoInterface[]
     *
     * Internal list allows to keep once built buckets info
     */
    protected array $bucketsInfoList = [];

    /**
     * @param array $config
     *
     * @throws ConfigException
     */
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
     * Build file system info by provided fs (bucket) label
     * Force mode allows to rebuild fs info for internal file systems info list
     *
     * @param string $fileSystem
     * @param bool|null $force
     *
     * @return FileSystemInfo\FileSystemInfoInterface
     *
     * @throws StorageException
     */
    public function buildFileSystemInfo(
        string $fileSystem,
        ?bool $force = false
    ): FileSystemInfo\FileSystemInfoInterface {
        if (!$this->hasServer($fileSystem)) {
            throw new ConfigException(
                \sprintf('Server %s was not found', $fileSystem)
            );
        }

        if (!$force && array_key_exists($fileSystem, $this->fileSystemsInfoList)) {
            return $this->fileSystemsInfoList[$fileSystem];
        }

        $serverInfo = $this->config[static::SERVERS_KEY][$fileSystem];

        if (!is_array($serverInfo)) {
            throw new ConfigException(
                \sprintf(
                    'File system info for %s was provided in wrong format, array expected, %s received',
                    $fileSystem,
                    gettype($serverInfo)
                )
            );
        }

        switch ($this->extractServerAdapter($serverInfo)) {
            case \League\Flysystem\Local\LocalFilesystemAdapter::class:
                $fsInfoDTO = new FileSystemInfo\LocalInfo($fileSystem, $serverInfo);
                break;
            case \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class:
            case \League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter::class:
                $fsInfoDTO = new FileSystemInfo\Aws\AwsS3Info($fileSystem, $serverInfo);
                break;
            default:
                throw new ConfigException('Adapter can\'t be identified for file system ' . $fileSystem);
        }

        $this->fileSystemsInfoList[$fileSystem] = $fsInfoDTO;

        return $this->fileSystemsInfoList[$fileSystem];
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
                \sprintf('Bucket %s was not found', $bucketLabel)
            );
        }

        if (!$force && array_key_exists($bucketLabel, $this->bucketsInfoList)) {
            return $this->bucketsInfoList[$bucketLabel];
        }

        $bucketInfo = $this->config[static::BUCKETS_KEY][$bucketLabel];

        $this->bucketsInfoList[$bucketLabel] = new BucketInfo(
            $bucketLabel,
            $bucketInfo[BucketInfoInterface::SERVER_KEY],
            $bucketInfo
        );

        return $this->bucketsInfoList[$bucketLabel];
    }

    private function extractServerAdapter(array $serverInfo): ?string
    {
        return $serverInfo[FileSystemInfo\FileSystemInfoInterface::ADAPTER_KEY] ?? null;
    }
}
