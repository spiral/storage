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
use Spiral\Storage\Exception\StorageException;
use Spiral\Streams\StreamableInterface;

/**
 * Abstraction level between remote storage and local filesystem. Provides set of generic file
 * operations.
 */
interface BucketInterface
{
    /**
     * Bucket name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get bucket prefix.
     *
     * @return string
     */
    public function getPrefix(): string;

    /**
     * Associated storage server instance.
     *
     * @return ServerInterface
     *
     * @throws StorageException
     */
    public function getServer(): ServerInterface;

    /**
     * Get bucket version with some options changed.
     *
     * $bucket->withOption('public', false)->put(...)
     *
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function withOption(string $name, $value): BucketInterface;

    /**
     * Get server specific bucket option or return default value.
     *
     * @param string $name
     * @param null   $default
     * @return mixed
     */
    public function getOption(string $name, $default = null);

    /**
     * Build object address using object name and bucket prefix. While using URL like prefixes
     * address can appear valid URI which can be used directly at frontend.
     *
     * @param string $name
     * @return string
     */
    public function getAddress(string $name): string;

    /**
     * Check if given name points to valid and existed location in bucket server.
     *
     * @param string $name
     * @return bool
     *
     * @throws BucketException
     */
    public function exists(string $name): bool;

    /**
     * Get object size or return false if object not found.
     *
     * @param string $name
     * @return int|null
     *
     * @throws BucketException
     */
    public function size(string $name): ?int;

    /**
     * Put given content under given name in associated bucket server. Must replace already existed
     * object.
     *
     * @param string                                              $name
     * @param string|StreamInterface|StreamableInterface|resource $source String can only be
     *                                                                    filename.
     * @return string Return inserted object address.
     *
     * @throws BucketException
     */
    public function put(string $name, $source): string;

    /**
     * Return PSR7 stream associated with bucket object content or trow and exception.
     *
     * @param string $name Storage object name.
     * @return StreamInterface
     *
     * @throws BucketException
     */
    public function getStream(string $name): StreamInterface;

    /**
     * Delete bucket object if it exists.
     *
     * @param string $name Storage object name.
     *
     * @throws BucketException
     */
    public function delete(string $name);

    /**
     * Rename storage object without changing it's bucket. Must return new address on success.
     *
     * @param string $oldName
     * @param string $newName
     * @return string
     *
     * @throws StorageException
     * @throws BucketException
     */
    public function rename(string $oldName, string $newName): string;

    /**
     * Copy storage object to another bucket. Method must return address which points to
     * new storage object.
     *
     * @param BucketInterface $destination
     * @param string          $name
     * @return string
     *
     * @throws BucketException
     */
    public function copy(BucketInterface $destination, string $name): string;

    /**
     * Move storage object data to another bucket. Method must return new object address on success.
     *
     * @param BucketInterface $destination
     * @param string          $name
     * @return string
     *
     * @throws BucketException
     */
    public function replace(BucketInterface $destination, string $name): string;
}