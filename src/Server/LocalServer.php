<?php declare(strict_types=1);
/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @package   spiralFramework
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2011
 */

namespace Spiral\Storage\Server;

use Psr\Http\Message\StreamInterface;
use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exception\ServerException;
use Spiral\Streams\StreamWrapper;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Provides abstraction level to work with data located in local filesystem.
 */
class LocalServer extends AbstractServer
{
    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function exists(BucketInterface $bucket, string $name): bool
    {
        return $this->files->exists($this->getPath($bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function size(BucketInterface $bucket, string $name): ?int
    {
        if (!$this->files->exists($this->getPath($bucket, $name))) {
            return null;
        }

        return $this->files->size($this->getPath($bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function put(BucketInterface $bucket, string $name, StreamInterface $stream): bool
    {
        $path = $this->getPath($bucket, $name);

        $mode = $bucket->getOption('mode', FilesInterface::RUNTIME);

        //Pre-ensuring location
        $this->files->ensureDirectory(dirname($path), $mode);

        $resource = StreamWrapper::getResource($stream);
        try {
            $target = fopen($path, 'w');
            if (stream_copy_to_stream($resource, $target) === false) {
                throw new ServerException("Unable to put to '{$path}'");
            }
        } finally {
            StreamWrapper::releaseUri($resource);
            fclose($resource);
        }

        return $this->files->setPermissions($path, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(BucketInterface $bucket, string $name): StreamInterface
    {
        if (!$this->exists($bucket, $name)) {
            throw new ServerException(
                "Unable to create stream for '{$name}', object does not exists"
            );
        }

        //Getting readonly stream
        return stream_for(fopen($this->getPath($bucket, $name), 'rb'));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(BucketInterface $bucket, string $name)
    {
        if (!$this->exists($bucket, $name)) {
            throw new ServerException("Unable to delete object, file not found");
        }

        $this->files->delete($this->getPath($bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function rename(BucketInterface $bucket, string $oldName, string $newName): bool
    {
        return $this->internalMove(
            $bucket,
            $this->getPath($bucket, $oldName),
            $this->getPath($bucket, $newName)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function copy(BucketInterface $bucket, BucketInterface $destination, string $name): bool
    {
        return $this->internalCopy(
            $destination,
            $this->getPath($bucket, $name),
            $this->getPath($destination, $name)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function replace(
        BucketInterface $bucket,
        BucketInterface $destination,
        string $name
    ): bool {
        return $this->internalMove(
            $destination,
            $this->getPath($bucket, $name),
            $this->getPath($destination, $name)
        );
    }

    /**
     * Move helper, ensure target directory existence, file permissions and etc.
     *
     * @param BucketInterface $bucket
     * @param string          $filename
     * @param string          $destination
     *
     * @return bool
     * @throws ServerException
     */
    protected function internalMove(
        BucketInterface $bucket,
        string $filename,
        string $destination
    ): bool {
        if (!$this->files->exists($filename)) {
            throw new ServerException("Unable to move '{$filename}', object does not exists");
        }

        $mode = $bucket->getOption('mode', FilesInterface::RUNTIME);

        //Pre-ensuring location
        $this->files->ensureDirectory(dirname($destination), $mode);

        if (!$this->files->move($filename, $destination)) {
            throw new ServerException("Unable to move '{$filename}' to '{$destination}'.");
        }

        return $this->files->setPermissions($destination, $mode);
    }

    /**
     * Copy helper, ensure target directory existence, file permissions and etc.
     *
     * @param BucketInterface $bucket
     * @param string          $filename
     * @param string          $destination
     *
     * @return bool
     * @throws ServerException
     */
    protected function internalCopy(
        BucketInterface $bucket,
        string $filename,
        string $destination
    ): bool {
        if (!$this->files->exists($filename)) {
            throw new ServerException("Unable to copy '{$filename}', object does not exists");
        }

        $mode = $bucket->getOption('mode', FilesInterface::RUNTIME);

        //Pre-ensuring location
        $this->files->ensureDirectory(dirname($destination), $mode);

        if (!$this->files->copy($filename, $destination)) {
            throw new ServerException("Unable to copy '{$filename}' to '{$destination}'");
        }

        return $this->files->setPermissions($destination, $mode);
    }

    /**
     * Get full file location on server including homedir.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return string
     */
    protected function getPath(BucketInterface $bucket, string $name): string
    {
        if (empty($this->options['home'])) {
            return $this->files->normalizePath($bucket->getOption('directory') . $name);
        }

        return $this->files->normalizePath(
            $this->options['home'] . '/' . $bucket->getOption('directory') . $name
        );
    }
}