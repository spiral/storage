<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Entities;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerAwareInterface;
use Spiral\Core\Container\InjectableInterface;
use Spiral\Debug\Traits\BenchmarkTrait;
use Spiral\Debug\Traits\LoggerTrait;
use Spiral\Files\Streams\StreamableInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exceptions\BucketException;
use Spiral\Storage\Exceptions\ServerException;
use Spiral\Storage\ServerInterface;
use Spiral\Storage\StorageManager;
use Zend\Diactoros\Stream;

/**
 * Default implementation of storage bucket.
 */
//todo: sprintf
class StorageBucket implements BucketInterface, LoggerAwareInterface, InjectableInterface
{
    use BenchmarkTrait, LoggerTrait;

    const INJECTOR = StorageManager::class;

    /** @var string */
    private $name = '';

    /** @var string */
    private $prefix = '';

    /** @var ServerInterface */
    private $server = null;

    /** @var array */
    private $options = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        string $name,
        string $prefix,
        array $options,
        ServerInterface $server
    ) {
        $this->name = $name;
        $this->prefix = $prefix;
        $this->options = $options;
        $this->server = $server;
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
    public function getServer(): ServerInterface
    {
        return $this->server;
    }

    /**
     * {@inheritdoc}
     */
    public function withOption(string $name, $value): BucketInterface
    {
        $bucket = clone $this;
        $bucket->options[$name] = $value;

        return $bucket;
    }

    /**
     * {@inheritdoc}
     */
    public function getOption(string $name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }


    /**
     * {@inheritdoc}
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAddress(string $address)
    {
        if (strpos($address, $this->prefix) === 0) {
            return strlen($this->prefix);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function buildAddress(string $name): string
    {
        return $this->prefix . $name;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name): bool
    {
        $this->getLogger()->info(sprintf(
                "",
                1
            ) .
            "Check existence of '{$this->buildAddress($name)}' in '{$this->getName()}' bucket."
        );

        $benchmark = $this->benchmark($this->getName(), "exists::{$this->buildAddress($name)}");
        try {
            return (bool)$this->server->exists($this, $name);
        } catch (ServerException$e) {
            throw new BucketException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $benchmark->complete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $name): ?int
    {
        $this->getLogger()->info(
            "Get size of '{$this->buildAddress($name)}' in '{$this->getName()}' bucket."
        );

        $benchmark = $this->benchmark($this->getName(), "size::{$this->buildAddress($name)}");
        try {
            return $this->server->size($this, $name);
        } catch (ServerException$e) {
            throw new BucketException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $benchmark->complete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $name, $source): string
    {
        $this->getLogger()->info(
            "Put '{$this->buildAddress($name)}' in '{$this->getName()}' bucket."
        );

        if ($source instanceof UploadedFileInterface || $source instanceof StreamableInterface) {
            //Known simplification for UploadedFile
            $source = $source->getStream();
        }

        if (is_resource($source)) {
            $source = new Stream($source, 'r');
        }

        $benchmark = $this->benchmark($this->getName(), "put::{$this->buildAddress($name)}");
        try {
            $this->server->put($this, $name, $source);

            //Reopening
            return $this->buildAddress($name);
        } catch (ServerException$e) {
            throw new BucketException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $benchmark->complete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function allocateFilename(string $name): string
    {
        $this->getLogger()->info(
            "Allocate filename of '{$this->buildAddress($name)}' in '{$this->getName()}' bucket."
        );

        $benchmark = $this->benchmark(
            $this->getName(), "filename::{$this->buildAddress($name)}"
        );

        try {
            return $this->getServer()->allocateFilename($this, $name);
        } catch (ServerException$e) {
            throw new BucketException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $benchmark->complete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function allocateStream(string $name): StreamInterface
    {
        $this->getLogger()->info(
            "Get stream for '{$this->buildAddress($name)}' in '{$this->getName()}' bucket."
        );

        $benchmark = $this->benchmark(
            $this->getName(), "stream::{$this->buildAddress($name)}"
        );

        try {
            return $this->getServer()->allocateStream($this, $name);
        } catch (ServerException$e) {
            throw new BucketException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $benchmark->complete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name)
    {
        $this->getLogger()->info(
            "Delete '{$this->buildAddress($name)}' in '{$this->getName()}' bucket."
        );

        $benchmark = $this->benchmark(
            $this->getName(), "delete::{$this->buildAddress($name)}"
        );

        try {
            $this->server->delete($this, $name);
        } catch (ServerException$e) {
            throw new BucketException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $benchmark->complete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $oldName, string $newName): string
    {
        if ($oldName == $newName) {
            return true;
        }

        $this->getLogger()->info(
            "Rename '{$this->buildAddress($oldName)}' to '{$this->buildAddress($newName)}' "
            . "in '{$this->getName()}' bucket."
        );

        $benchmark = $this->benchmark(
            $this->getName(), "rename::{$this->buildAddress($oldName)}"
        );

        try {
            $this->server->rename($this, $oldName, $newName);

            return $this->buildAddress($newName);
        } catch (ServerException$e) {
            throw new BucketException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $benchmark->complete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copy(BucketInterface $destination, string $name): string
    {
        if ($destination === $this) {
            return $this->buildAddress($name);
        }

        //Internal copying
        if ($this->getServer() === $destination->getServer()) {
            $this->getLogger()->info(
                "Internal copy of '{$this->buildAddress($name)}' "
                . "to '{$destination->buildAddress($name)}' in '{$this->getName()}' bucket."
            );

            $benchmark = $this->benchmark(
                $this->getName(), "copy::{$this->buildAddress($name)}"
            );

            try {
                $this->getServer()->copy($this, $destination, $name);
            } catch (ServerException$e) {
                throw new BucketException($e->getMessage(), $e->getCode(), $e);
            } finally {
                $benchmark->complete();
            }

        } else {
            $this->getLogger()->info(
                "External copy of '{$this->getName()}'.'{$this->buildAddress($name)}' "
                . "to '{$destination->getName()}'.'{$destination->buildAddress($name)}'."
            );

            $destination->put($name, $stream = $this->allocateStream($name));
            $stream->detach();
        }

        return $destination->buildAddress($name);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(
        BucketInterface $destination,
        string $name,
        string $rename = null
    ): string {
        if ($destination === $this) {
            return $this->buildAddress($name);
        }

        //Internal copying
        if ($this->getServer() === $destination->getServer()) {
            $this->getLogger()->info(
                "Internal move '{$this->buildAddress($name)}' "
                . "to '{$destination->buildAddress($name)}' in '{$this->getName()}' bucket."
            );

            $benchmark = $this->benchmark(
                $this->getName(), "replace::{$this->buildAddress($name)}"
            );

            try {
                $this->getServer()->replace($this, $destination, $name);
            } catch (ServerException$e) {
                throw new BucketException($e->getMessage(), $e->getCode(), $e);
            } finally {
                $benchmark->complete();
            }
        } else {
            $this->getLogger()->info(
                "External move '{$this->getName()}'.'{$this->buildAddress($name)}'"
                . " to '{$destination->getName()}'.'{$destination->buildAddress($name)}'."
            );

            //Copying using temporary stream (buffer)
            $destination->put($name, $stream = $this->allocateStream($name));

            if ($stream->detach()) {
                //Dropping temporary stream
                $this->delete($name);
            }
        }

        return $destination->buildAddress($name);
    }
}
