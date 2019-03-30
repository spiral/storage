<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage;

use Spiral\Core\Container\InjectorInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Storage\Config\BucketResolver;
use Spiral\Storage\Config\StorageConfig;
use Spiral\Storage\Exception\StorageException;

/**
 * Default implementation of StorageInterface. Please note that StorageManager component provides
 * general purpose abstraction for file storage, it does not and will not support directory
 * listings and features specific to storage adapter (however such functionality can be added at
 * server level).
 */
final class StorageManager implements StorageInterface, InjectorInterface
{
    /** @var StorageConfig */
    private $config;

    /** @var BucketResolver */
    private $resolver;

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
        $this->resolver = $config->getResolver();
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        foreach ($this->servers as $server) {
            $server->disconnect();
        }
    }

    /**
     * @param BucketInterface $bucket
     */
    public function addBucket(BucketInterface $bucket)
    {
        if (isset($this->buckets[$bucket->getName()])) {
            throw new StorageException("Bucket `{$bucket->getName()}` already exists.");
        }

        $this->buckets[$bucket->getName()] = $bucket;
        $this->resolver->setBucket($bucket->getName(), $bucket->getPrefix());
    }

    /**
     * {@inheritdoc}
     */
    public function getBucket(string $bucket): BucketInterface
    {
        if (empty($bucket)) {
            throw new StorageException("Bucket name is not specified.");
        }

        $bucket = $this->config->resolveAlias($bucket);
        if (isset($this->buckets[$bucket])) {
            return $this->buckets[$bucket];
        }

        $config = $this->config->getBucket($bucket);

        return $this->buckets[$bucket] = new StorageBucket(
            $this->getServer($config['server']),
            $bucket,
            $config['prefix'],
            $config['options']
        );
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
            throw new StorageException("Undefined storage server `{$server}`.");
        }

        return $this->servers[$server] = $this->config->getServer($server)->resolve($this->factory);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $bucket, string $name, $source = ''): ObjectInterface
    {
        return $this->open($this->getBucket($bucket)->put($name, $source));
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $address): ObjectInterface
    {
        $bucket = $this->resolver->resolveBucket($address, $name);

        return new StorageObject($this->getBucket($bucket), $name);
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
}
