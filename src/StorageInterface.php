<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage;

use Psr\Http\Message\StreamInterface;
use Spiral\Streams\StreamableInterface;
use Spiral\Storage\Exception\BucketException;
use Spiral\Storage\Exception\ObjectException;
use Spiral\Storage\Exception\ServerException;
use Spiral\Storage\Exception\StorageException;

/**
 * Abstraction level to work with local and remote files represented using storage objects and
 * buckets.
 */
interface StorageInterface
{
    /**
     * Register new bucket using it's options, server and prefix.
     *
     * @param string                 $name
     * @param string                 $prefix
     * @param ServerInterface|string $server  Instance of alias.
     * @param array                  $options Server specific options.
     *
     * @return BucketInterface
     *
     * @throws StorageException
     */
    public function createBucket(
        string $name,
        string $prefix,
        $server,
        array $options = []
    ): BucketInterface;

    /**
     * Get bucket by it's name.
     *
     * @param string $bucket
     *
     * @return BucketInterface
     *
     * @throws StorageException
     */
    public function getBucket(string $bucket): BucketInterface;

    /**
     * Find bucket instance by object address.
     *
     * @param string $address
     * @param string $name Name stripped from address.
     *
     * @return BucketInterface
     *
     * @throws StorageException
     */
    public function locateBucket(string $address, string &$name = null): BucketInterface;

    /**
     * Get or create instance of storage server.
     *
     * @param string $server
     *
     * @return ServerInterface
     *
     * @throws StorageException
     */
    public function getServer(string $server): ServerInterface;

    /**
     * Put object data into specified bucket under provided name. Should support filenames, PSR7
     * streams and streamable objects. Must create empty object if source empty.
     *
     * @param string|BucketInterface                    $bucket
     * @param string                                    $name
     * @param mixed|StreamInterface|StreamableInterface $source
     *
     * @return ObjectInterface
     *
     * @throws StorageException
     * @throws BucketException
     * @throws ServerException
     */
    public function put(string $bucket, string $name, $source = ''): ObjectInterface;

    /**
     * Create instance of storage object using it's address.
     *
     * @param string $address
     *
     * @return ObjectInterface
     *
     * @throws StorageException
     * @throws ObjectException
     */
    public function open(string $address): ObjectInterface;
}