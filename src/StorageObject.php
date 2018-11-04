<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage;

use Psr\Http\Message\StreamInterface;
use Spiral\Storage\Exception\BucketException;
use Spiral\Storage\Exception\ObjectException;

/**
 * Default implementation of storage object. This is immutable class.
 */
final class StorageObject implements ObjectInterface
{
    /** @var BucketInterface */
    private $bucket = null;

    /** @var string */
    private $name = false;

    /**
     * @param BucketInterface $bucket
     * @param string          $name
     */
    public function __construct(BucketInterface $bucket, string $name)
    {
        $this->bucket = $bucket;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddress(): string
    {
        return $this->bucket->buildAddress($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getBucket(): BucketInterface
    {
        return $this->bucket;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(): bool
    {
        return $this->bucket->exists($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->bucket->size($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function localFilename(): string
    {
        try {
            return $this->bucket->allocateFilename($this->name);
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(): StreamInterface
    {
        try {
            return $this->bucket->allocateStream($this->name);
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        try {
            $this->bucket->delete($this->name);
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $newName): ObjectInterface
    {
        try {
            $this->bucket->rename($this->name, $newName);
            $this->name = $newName;
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function copy(BucketInterface $destination): ObjectInterface
    {
        $object = clone $this;
        $object->bucket = $destination;

        try {
            $this->bucket->copy($destination, $this->name);
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(BucketInterface $destination): ObjectInterface
    {
        try {
            $this->bucket->replace($destination, $this->name);
            $this->bucket = $destination;
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->bucket->buildAddress($this->name);
    }
}