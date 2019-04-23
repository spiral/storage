<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Storage\Server;

use Spiral\Files\Files;
use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exception\ServerException;
use Spiral\Storage\ServerInterface;

/**
 * AbstractServer implementation with different naming.
 */
abstract class AbstractServer implements ServerInterface
{
    /**
     * Default mimetype to be used when nothing else can be applied.
     */
    const DEFAULT_MIMETYPE = 'application/octet-stream';

    /** @var FilesInterface */
    protected $files;

    /** @var array */
    protected $options = [];

    /**
     * @param array          $options Server specific options.
     * @param FilesInterface $files   Required for operations with local filesystem.
     */
    public function __construct(array $options, FilesInterface $files = null)
    {
        $this->files = $files ?? new Files();
        $this->options = $options + $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function copy(BucketInterface $bucket, BucketInterface $destination, string $name): bool
    {
        return $this->put($destination, $name, $this->getStream($bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function replace(BucketInterface $bucket, BucketInterface $destination, string $name): bool
    {
        if ($this->copy($bucket, $destination, $name)) {
            $this->delete($bucket, $name);

            return true;
        }

        throw new ServerException("Unable to copy '{$name}' to new bucket");
    }

    /**
     * Destroy the server and close the connection.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}