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
        if (!array_key_exists(static::SERVERS_KEY, $config) || empty($config[static::SERVERS_KEY])) {
            throw new ConfigException('Servers must be defined for storage work');
        }

        foreach (array_keys($config[static::SERVERS_KEY]) as $key => $server) {
            if (!is_string($server) || empty($server)) {
                throw new ConfigException(
                    \sprintf(
                        'Server `%s` has incorrect key - string expected %s received',
                        is_scalar($server) && !empty($server) ? $server : '--non-displayable--' . "[$key]",
                        empty($server) ? 'empty val' : gettype($server)
                    )
                );
            }
        }

        if (!array_key_exists(static::BUCKETS_KEY, $config) || empty($config[static::BUCKETS_KEY])) {
            throw new ConfigException('Buckets must be defined for storage work');
        }

        foreach (array_keys($config[static::BUCKETS_KEY]) as $key => $bucket) {
            if (!is_string($bucket) || empty($bucket)) {
                throw new ConfigException(
                    \sprintf(
                        'Bucket `%s` has incorrect key - string expected %s received',
                        is_scalar($bucket) && !empty($bucket) ? $bucket : '--non-displayable--' . "[$key]",
                        empty($bucket) ? 'empty val' : gettype($bucket)
                    )
                );
            }
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
        return array_key_exists($key, $this->config[static::SERVERS_KEY])
            && is_array($this->config[static::SERVERS_KEY][$key]);
    }

    public function getBucketsKeys(): array
    {
        return array_key_exists(static::BUCKETS_KEY, $this->config)
            ? array_keys($this->config[static::BUCKETS_KEY])
            : [];
    }

    public function hasBucket(string $key): bool
    {
        return array_key_exists($key, $this->config[static::BUCKETS_KEY])
            && is_array($this->config[static::BUCKETS_KEY][$key]);
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
     * @param string $fs
     * @param bool|null $force
     *
     * @return FileSystemInfo\FileSystemInfoInterface
     *
     * @throws StorageException
     */
    public function buildFileSystemInfo(string $fs, ?bool $force = false): FileSystemInfo\FileSystemInfoInterface
    {
        if (!$this->hasBucket($fs)) {
            throw new ConfigException(
                \sprintf('Bucket `%s` was not found', $fs)
            );
        }

        if (!$force && array_key_exists($fs, $this->fileSystemsInfoList)) {
            return $this->fileSystemsInfoList[$fs];
        }

        $bucketInfo = $this->buildBucketInfo($fs);

        if (!$this->hasServer($bucketInfo->getServer())) {
            throw new ConfigException(
                \sprintf(
                    'Server `%s` info for file system `%s` was not detected',
                    $bucketInfo->getServer(),
                    $fs
                )
            );
        }

        $serverInfo = $this->config[static::SERVERS_KEY][$bucketInfo->getServer()];

        switch ($this->extractServerAdapter($serverInfo)) {
            case \League\Flysystem\Local\LocalFilesystemAdapter::class:
                $fsInfoDTO = new FileSystemInfo\LocalInfo($fs, $serverInfo);
                break;
            case \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class:
            case \League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter::class:
                $serverInfo[FileSystemInfo\Aws\AwsS3Info::OPTIONS_KEY] = array_merge(
                    [
                        FileSystemInfo\Aws\AwsS3Info::BUCKET_KEY => $bucketInfo->getOption(
                            BucketInfoInterface::BUCKET_KEY
                        )
                    ],
                    $serverInfo[FileSystemInfo\Aws\AwsS3Info::OPTIONS_KEY]
                );

                $fsInfoDTO = new FileSystemInfo\Aws\AwsS3Info($fs, $serverInfo);
                break;
            default:
                throw new ConfigException(
                    \sprintf('Adapter can\'t be identified for file system `%s`', $fs)
                );
        }

        $this->fileSystemsInfoList[$fs] = $fsInfoDTO;
        $bucketInfo->setFileSystemInfo($fsInfoDTO);

        return $this->fileSystemsInfoList[$fs];
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
                \sprintf('Bucket `%s` was not found', $bucketLabel)
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
