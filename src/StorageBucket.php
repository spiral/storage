<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerAwareInterface;
use Spiral\Core\Container\InjectableInterface;
use Spiral\Debug\Traits\BenchmarkTrait;
use Spiral\Logger\Traits\LoggerTrait;
use Spiral\Storage\Exception\BucketException;
use Spiral\Storage\Exception\ServerException;
use Spiral\Streams\StreamableInterface;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Default implementation of storage bucket.
 */
final class StorageBucket implements BucketInterface, LoggerAwareInterface, InjectableInterface
{
    use BenchmarkTrait, LoggerTrait;

    const INJECTOR = StorageManager::class;

    /** @var ServerInterface */
    private $server;

    /** @var string */
    private $name;

    /** @var string */
    private $prefix;

    /** @var array */
    private $options = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ServerInterface $server,
        string $name,
        string $prefix,
        array $options
    ) {
        $this->server = $server;
        $this->name = $name;
        $this->prefix = $prefix;
        $this->options = $options;
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
    public function getPrefix(): string
    {
        return $this->prefix;
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
    public function getAddress(string $name): string
    {
        return $this->prefix . $name;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name): bool
    {
        $this->getLogger()->info(sprintf(
            "check existence of '%s' in '%s' bucket.",
            $this->getAddress($name),
            $this->getName()
        ));

        $benchmark = $this->benchmark(
            $this->getName(),
            "exists::{$this->getAddress($name)}"
        );

        try {
            return (bool)$this->server->exists($this, $name);
        } catch (ServerException $e) {
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
        $this->getLogger()->info(sprintf(
            "get size of '%s' in '%s' bucket.",
            $this->getAddress($name),
            $this->getName()
        ));

        $benchmark = $this->benchmark(
            $this->getName(),
            "size::{$this->getAddress($name)}"
        );

        try {
            return $this->server->size($this, $name);
        } catch (ServerException $e) {
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
        $this->getLogger()->info(sprintf(
            "put '%s' in '%s' bucket.",
            $this->getAddress($name),
            $this->getName()
        ));

        $stream = $this->castStream($source);

        $benchmark = $this->benchmark(
            $this->getName(),
            "put::{$this->getAddress($name)}"
        );

        try {
            $this->server->put($this, $name, $stream);
            //    $stream->detach();

            //Reopening
            return $this->getAddress($name);
        } catch (ServerException $e) {
            throw new BucketException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $benchmark->complete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(string $name): StreamInterface
    {
        $this->getLogger()->info(sprintf(
            "get stream for '%s' in '%s' bucket.",
            $this->getAddress($name),
            $this->getName()
        ));

        $benchmark = $this->benchmark(
            $this->getName(),
            "stream::{$this->getAddress($name)}"
        );

        try {
            return $this->getServer()->getStream($this, $name);
        } catch (ServerException $e) {
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
        $this->getLogger()->info(sprintf(
            "delete '%s' in '%s' bucket.",
            $this->getAddress($name),
            $this->getName()
        ));

        $benchmark = $this->benchmark(
            $this->getName(),
            "delete::{$this->getAddress($name)}"
        );

        try {
            $this->server->delete($this, $name);
        } catch (ServerException $e) {
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
            return $oldName;
        }

        $this->getLogger()->info(sprintf(
            "rename '%s' to '%s' in '%s' bucket.",
            $this->getAddress($oldName),
            $this->getAddress($newName),
            $this->getName()
        ));

        $benchmark = $this->benchmark(
            $this->getName(),
            "rename::{$this->getAddress($oldName)}"
        );

        try {
            $this->server->rename($this, $oldName, $newName);

            return $this->getAddress($newName);
        } catch (ServerException $e) {
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
            return $this->getAddress($name);
        }

        //Internal copying
        if ($this->getServer() === $destination->getServer()) {
            $this->getLogger()->info(sprintf(
                "internal copy of '%s' to '%s' from '%s' bucket.",
                $this->getAddress($name),
                $destination->getAddress($name),
                $this->getName()
            ));

            $benchmark = $this->benchmark(
                $this->getName(),
                "copy::{$this->getAddress($name)}"
            );

            try {
                $this->getServer()->copy($this, $destination, $name);
            } catch (ServerException $e) {
                throw new BucketException($e->getMessage(), $e->getCode(), $e);
            } finally {
                $benchmark->complete();
            }

        } else {
            $this->getLogger()->info(sprintf(
                "external copy of '%s'.'%s' to '%s'.'%s'.",
                $this->getName(),
                $this->getAddress($name),
                $destination->getName(),
                $destination->getAddress($name)
            ));

            $destination->put($name, $stream = $this->getStream($name));
            $stream->detach();
        }

        return $destination->getAddress($name);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(BucketInterface $destination, string $name): string
    {
        if ($destination === $this) {
            return $this->getAddress($name);
        }

        //Internal copying
        if ($this->getServer() === $destination->getServer()) {
            $this->getLogger()->info(sprintf(
                "internal move of '%s' to '%s' from '%s' bucket.",
                $this->getAddress($name),
                $destination->getAddress($name),
                $this->getName()
            ));

            $benchmark = $this->benchmark(
                $this->getName(),
                "replace::{$this->getAddress($name)}"
            );

            try {
                $this->getServer()->replace($this, $destination, $name);
            } catch (ServerException $e) {
                throw new BucketException($e->getMessage(), $e->getCode(), $e);
            } finally {
                $benchmark->complete();
            }
        } else {
            $this->getLogger()->info(sprintf(
                "external move of '%s'.'%s' to '%s'.'%s'.",
                $this->getName(),
                $this->getAddress($name),
                $destination->getName(),
                $destination->getAddress($name)
            ));

            //Copying using temporary stream (buffer)
            $destination->put($name, $stream = $this->getStream($name));

            if ($stream->detach()) {
                //Dropping temporary stream
                $this->delete($name);
            }
        }

        return $destination->getAddress($name);
    }

    /**
     * Cast stream associated with origin data.
     *
     * @param string|StreamInterface|resource $source
     *
     * @return StreamInterface
     */
    protected function castStream($source): StreamInterface
    {
        if ($source instanceof UploadedFileInterface || $source instanceof StreamableInterface) {
            $source = $source->getStream();
        }

        if ($source instanceof StreamInterface) {
            //This step is important to prevent user errors
            $source->rewind();

            return $source;
        }

        if (is_resource($source)) {
            return stream_for($source);
        }

        if (empty($source)) {
            //Guzzle?
            return stream_for('');
        }

        if ($this->isFilename($source)) {
            //Must never pass user string in here, use Stream
            return stream_for(fopen($source, 'rb'));
        }

        //We do not allow source names in a string form
        throw new BucketException(
            "Source must be a valid resource, stream or filename, invalid value given"
        );
    }

    /**
     * Check if given string is proper filename.
     *
     * @param mixed $source
     *
     * @return bool
     */
    protected function isFilename($source): bool
    {
        if (!is_string($source)) {
            return false;
        }

        if (!preg_match('/[^A-Za-z0-9.#\\-$]/', $source)) {
            return false;
        }

        //To filter out binary strings
        $source = strval(str_replace("\0", "", $source));

        return file_exists($source);
    }
}
