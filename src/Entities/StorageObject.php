<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Entities;

use Psr\Http\Message\StreamInterface;
use Spiral\Core\Exceptions\ScopeException;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exceptions\BucketException;
use Spiral\Storage\Exceptions\ObjectException;
use Spiral\Storage\ObjectInterface;
use Spiral\Storage\StorageInterface;

/**
 * Default implementation of storage object. This is immutable class.
 */
class StorageObject implements ObjectInterface
{
    /** @var BucketInterface */
    private $bucket = null;

    /** @var string */
    private $address = false;

    /** @var string */
    private $name = false;

    /**
     * @invisible
     * @var StorageInterface
     */
    protected $storage = null;

    /**
     * @param string                $address
     * @param StorageInterface|null $storage
     *
     * @throws ScopeException
     */
    public function __construct(string $address, StorageInterface $storage)
    {
        $this->storage = $storage;

        $this->address = $address;
        $this->bucket = $this->storage->locateBucket($address, $this->name);

        if (empty($this->name)) {
            throw new ObjectException("Unable to create StorageObject with empty name");
        }

        if (empty($this->bucket)) {
            throw new ObjectException("Unable to resolve bucket for address '{$address}'");
        }
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
        return $this->address;
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
            $this->address = $this->bucket->rename($this->name, $newName);
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }

        $this->name = $newName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($destination): ObjectInterface
    {
        if (is_string($destination)) {
            $destination = $this->storage->getBucket($destination);
        }

        $object = clone $this;
        $object->bucket = $destination;
        try {
            $object->address = $this->bucket->copy($destination, $this->name);
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function replace($destination): ObjectInterface
    {
        if (is_string($destination)) {
            $destination = $this->storage->getBucket($destination);
        }
        try {
            $this->address = $this->bucket->replace($destination, $this->name);
        } catch (BucketException $e) {
            throw new ObjectException($e->getMessage(), $e->getCode(), $e);
        }

        $this->bucket = $destination;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->address;
    }
}