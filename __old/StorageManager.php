<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage;

use Spiral\Core\Container\InjectorInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Storage\Config\StorageConfig;
use Spiral\Storage\Exception\StorageException;

/**
 * Default implementation of StorageInterface. Please note that StorageManager component provides
 * general purpose abstraction for file storage, it does not and will not support directory
 * listings and features specific to storage adapter (however such functionality can be added at
 * server level).
 */
class StorageManager implements StorageInterface, InjectorInterface
{
    /** @var StorageConfig */
    private $config;

    /**  @var BucketInterface[] */
    private $buckets = [];

    /** @var ServerInterface[] */
    private $servers = [];

    /** @var FactoryInterface */
    private $factory;

    /**
     * @param StorageConfig    $config
     * @param FactoryInterface $factory
     */
    public function __construct(StorageConfig $config, FactoryInterface $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
    }

    // todo: work it out

    /**
     * @param BucketInterface $bucket
     *
     * @return self
     *
     * @throws StorageException
     */
    public function addBucket(BucketInterface $bucket): StorageManager
    {
        if (isset($this->buckets[$bucket->getName()])) {
            throw new StorageException("Unable to create bucket '{$bucket->getName()}', already exists");
        }

        $this->buckets[$bucket->getName()] = $bucket;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createBucket(
        string $name,
        string $prefix,
        $server,
        array $options = []
    ): BucketInterface {
        if (isset($this->buckets[$name])) {
            throw new StorageException("Unable to create bucket '{$name}', already exists");
        }

        //One of default implementation options
        $storage = $this;

        if (!$server instanceof ServerInterface) {
            $server = $this->getServer($server);
        }

        $bucket = $this->factory->make(
            StorageBucket::class,
            compact('storage', 'prefix', 'options', 'server')
        );

        $this->addBucket($bucket);

        return $bucket;
    }

    /**
     * {@inheritdoc}
     */
    public function getBucket(string $bucket): BucketInterface
    {
        if (empty($bucket)) {
            throw new StorageException("Unable to fetch bucket, name can not be empty");
        }

        $bucket = $this->config->resolveAlias($bucket);

        if (isset($this->buckets[$bucket])) {
            return $this->buckets[$bucket];
        }

        throw new StorageException("Unable to fetch bucket '{$bucket}', no presets found");
    }

    /**
     * Add server.
     *
     * @param string          $name
     * @param ServerInterface $server
     *
     * @return $this
     *
     * @throws StorageException
     */
    public function addServer(string $name, ServerInterface $server): self
    {
        if (isset($this->servers[$name])) {
            throw new StorageException(
                "Unable to set storage server '{$server}', name is already taken"
            );
        }

        $this->servers[$name] = $server;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getServer(string $server): ServerInterface
    {
        if (isset($this->servers[$server])) {
            return $this->servers[$server];
        }

        if (!$this->config->hasServer($server)) {
            throw new StorageException("Undefined storage server '{$server}'");
        }

        return $this->servers[$server] = $this->factory->make(
            $this->config->serverClass($server),
            $this->config->serverOptions($server)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $address): ObjectInterface
    {
        return new StorageObject($address, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function locateBucket(string $address, string &$name = null): BucketInterface
    {
        /**
         * @var BucketInterface $bestBucket
         */
        $bestBucket = null;
        foreach ($this->buckets as $bucket) {
            if (!empty($prefixLength = $bucket->hasAddress($address))) {
                if (empty($bestBucket) || strlen($bestBucket->getPrefix()) < $prefixLength) {
                    $bestBucket = $bucket;
                    $name = substr($address, $prefixLength);
                }
            }
        }

        if (empty($bestBucket)) {
            throw new StorageException("Unable to locate bucket for a given address '{$address}'");
        }

        return $bestBucket;
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $bucket, string $name, $source = ''): ObjectInterface
    {
        $bucket = is_string($bucket) ? $this->getBucket($bucket) : $bucket;

        return $this->open($bucket->put($name, $source));
    }

    /**
     * {@inheritdoc}
     */
    public function createInjection(\ReflectionClass $class, string $context = null)
    {
        if (empty($context)) {
            throw new StorageException("Storage bucket can be requested without specified context");
        }

        return $this->getBucket($context);
    }

    /**
     * Create bucket based configuration settings.
     *
     * @param string $name
     * @param array  $bucket
     *
     * @return StorageBucket
     *
     * @throws StorageException
     */
    private function makeBucket(string $name, array $bucket): StorageBucket
    {
        $parameters = $bucket + compact('name');
        unset($parameters['server']);

        if (!array_key_exists('options', $bucket)) {
            throw new StorageException("Bucket configuration must include options");
        }

        if (!array_key_exists('prefix', $bucket)) {
            throw new StorageException("Bucket configuration must include prefix");
        }

        if (!array_key_exists('server', $bucket)) {
            throw new StorageException("Bucket configuration must include server id");
        }

        return $this->factory->make(
            StorageBucket::class,
            $parameters + ['server' => $this->getServer($bucket['server'])]
        );
    }
}
