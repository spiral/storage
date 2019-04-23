<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Storage;

use Psr\Http\Message\StreamInterface;
use Spiral\Storage\Exception\BucketException;
use Spiral\Streams\StreamableInterface;

/**
 * Representation of a single storage object. Technically this is only helper interface, does not
 * contain any important logic rather than dedicating operations to container.
 */
interface ObjectInterface extends StreamableInterface
{
    /**
     * Get object name inside parent bucket.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get full object address.
     *
     * @return string
     */
    public function getAddress(): string;

    /**
     * Get associated bucket instance.
     *
     * @return BucketInterface
     */
    public function getBucket();

    /**
     * Check if object exists.
     *
     * @return bool
     *
     * @throws BucketException
     */
    public function exists(): bool;

    /**
     * Get object size or return false of object does not exists.
     *
     * @return int|bool
     *
     * @throws BucketException
     */
    public function getSize(): ?int;

    /**
     * Return stream associated with the object content. All streams must be closed!
     *
     * @return StreamInterface
     * @throws BucketException
     */
    public function getStream(): StreamInterface;

    /**
     * Delete object from associated bucket.
     *
     * @throws BucketException
     */
    public function delete();

    /**
     * Rename storage object without changing it's bucket.
     *
     * @param string $newName
     * @return self
     *
     * @throws BucketException
     */
    public function rename(string $newName): ObjectInterface;

    /**
     * Copy storage object to another bucket. Method must return ObjectInterface which points to
     * new storage object.
     *
     * @param BucketInterface $destination
     * @return self
     *
     * @throws BucketException
     */
    public function copy(BucketInterface $destination): ObjectInterface;

    /**
     * Move storage object data to another bucket.
     *
     * @param BucketInterface $destination
     * @return self
     *
     * @throws BucketException
     */
    public function replace(BucketInterface $destination): ObjectInterface;

    /**
     * Must be serialized into object address.
     *
     * @return string
     */
    public function __toString(): string;
}