<?php
/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @package   spiralFramework
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2011
 */

namespace Spiral\Storage\Servers;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Spiral\Core\Component;
use Spiral\Files\FileManager;
use Spiral\Files\FilesInterface;
use Spiral\Files\Streams\StreamableInterface;
use Spiral\Files\Streams\StreamWrapper;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exceptions\ServerException;
use Spiral\Storage\ServerInterface;

/**
 * AbstractServer implementation with different naming.
 */
abstract class AbstractServer extends Component implements ServerInterface
{
    /**
     * Default mimetype to be used when nothing else can be applied.
     */
    const DEFAULT_MIMETYPE = 'application/octet-stream';

    /**
     * @var FilesInterface
     */
    protected $files;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array          $options Server specific options.
     * @param FilesInterface $files   Required for operations with local filesystem.
     */
    public function __construct(array $options, FilesInterface $files = null)
    {
        $this->files = $files ?? new FileManager();
        $this->options = $options + $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function allocateFilename(BucketInterface $bucket, string $name): string
    {
        if (empty($stream = $this->allocateStream($bucket, $name))) {
            throw new ServerException("Unable to allocate local filename for '{$name}'");
        }

        //Default implementation will use stream to create temporary filename, such filename
        //can't be used outside php scope
        return StreamWrapper::localFilename($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function copy(BucketInterface $bucket, BucketInterface $destination, string $name): bool
    {
        return $this->put($destination, $name, $this->allocateStream($bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function replace(
        BucketInterface $bucket,
        BucketInterface $destination,
        string $name
    ): bool {
        if ($this->copy($bucket, $destination, $name)) {
            $this->delete($bucket, $name);

            return true;
        }

        throw new ServerException("Unable to copy '{$name}' to new bucket");
    }

    /**
     * Cast local filename to be used in file based methods and etc.
     *
     * @param string|StreamInterface|resource $source
     *
     * @return string
     *
     * @throws ServerException
     */
    protected function castFilename($source): string
    {
        if (empty($source)) {
            return StreamWrapper::localFilename(\GuzzleHttp\Psr7\stream_for(''));
        }

        if (is_string($source)) {
            if ($this->isFilename($source)) {
                $source = \GuzzleHttp\Psr7\stream_for(fopen($source, 'rb'));
            } else {
                throw new ServerException(
                    "Source must be a valid resource, stream or filename, invalid value given"
                );
            }
        }

        if ($source instanceof UploadedFileInterface || $source instanceof StreamableInterface) {
            $source = $source->getStream();
        }

        if ($source instanceof StreamInterface) {
            return StreamWrapper::localFilename($source);
        }

        throw new ServerException("Unable to get filename for non Stream instance");
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

        if (empty($source)) {
            //Guzzle?
            return \GuzzleHttp\Psr7\stream_for('');
        }

        if ($this->isFilename($source)) {
            //Must never pass user string in here, use Stream
            return \GuzzleHttp\Psr7\stream_for(fopen($source, 'rb'));
        }

        //We do not allow source names in a string form
        throw new ServerException(
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