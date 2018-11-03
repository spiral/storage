<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage;

use Psr\Http\Message\StreamInterface;
use Spiral\Storage\Exception\ServerException;

/**
 * Server provide storage specific abstraction level. Must implement every low level storage
 * operations.
 *
 * Server can only work with buckets which are configured specifically for that adapter.
 */
interface ServerInterface
{
    /**
     * Check if object exists at server under specified bucket. Must return false if object does not
     * exists.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return bool
     * @throws ServerException
     */
    public function exists(BucketInterface $bucket, string $name): bool;

    /**
     * Get object size in specified bucket or return false.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return int|null
     * @throws ServerException
     */
    public function size(BucketInterface $bucket, string $name): ?int;

    /**
     * Put object data into specified bucket under given name, must replace existed data.
     *
     * @param BucketInterface                 $bucket
     * @param string                          $name
     * @param string|StreamInterface|resource $source
     *
     * @return bool
     * @throws ServerException
     */
    public function put(BucketInterface $bucket, string $name, $source): bool;

    /**
     * Must return filename which is valid in associated FilesInterface instance. Must trow an
     * exception if object does not exists. Filename can be temporary and should not be used
     * between sessions.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return string
     * @throws ServerException
     */
    public function allocateFilename(BucketInterface $bucket, string $name): string;

    /**
     * Return PSR7 stream associated with bucket object content or trow and exception.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return StreamInterface
     * @throws ServerException
     */
    public function allocateStream(BucketInterface $bucket, string $name): StreamInterface;

    /**
     * Delete bucket object if it exists.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @throws ServerException
     */
    public function delete(BucketInterface $bucket, string $name);

    /**
     * Rename storage object without changing it's bucket.
     *
     * @param BucketInterface $bucket
     * @param string          $oldName
     * @param string          $newName
     *
     * @return bool
     * @throws ServerException
     */
    public function rename(BucketInterface $bucket, string $oldName, string $newName): bool;

    /**
     * Copy storage object to another bucket. Both buckets must belong to same server.
     *
     * @param BucketInterface $bucket
     * @param BucketInterface $destination
     * @param string          $name
     *
     * @return bool
     * @throws ServerException
     */
    public function copy(BucketInterface $bucket, BucketInterface $destination, string $name): bool;

    /**
     * Move storage object data to another bucket. Both buckets must belong to same server.
     *
     * @param BucketInterface $bucket
     * @param BucketInterface $destination
     * @param string          $name
     *
     * @return bool
     * @throws ServerException
     */
    public function replace(
        BucketInterface $bucket,
        BucketInterface $destination,
        string $name
    ): bool;
}