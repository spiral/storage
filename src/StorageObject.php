<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage;

use Psr\Http\Message\StreamInterface;

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
        return $this->bucket->getAddress($this->name);
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
        return $this->bucket->allocateFilename($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(): StreamInterface
    {
        return $this->bucket->allocateStream($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->bucket->delete($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $newName): ObjectInterface
    {
        $this->bucket->rename($this->name, $newName);
        $this->name = $newName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function copy(BucketInterface $destination): ObjectInterface
    {
        $object = clone $this;
        $object->bucket = $destination;

        $this->bucket->copy($destination, $this->name);

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(BucketInterface $destination): ObjectInterface
    {
        $this->bucket->replace($destination, $this->name);
        $this->bucket = $destination;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->bucket->getAddress($this->name);
    }
}