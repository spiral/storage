<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Storage\Server;

use MongoDB\Database;
use MongoDB\Driver\Manager;
use MongoDB\GridFS\Bucket;
use Psr\Http\Message\StreamInterface;
use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exception\ServerException;
use Spiral\Streams\StreamWrapper;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Provides abstraction level to work with data located in GridFS storage.
 */
class GridFSServer extends AbstractServer
{

    /**
     * @invisible
     * @var array
     */
    protected $options = [
        'connection' => 'mongodb://localhost:27017',
        'database'   => ''
    ];

    /** @var Database */
    private $database;

    /**
     * @param array               $options
     * @param FilesInterface|null $files
     */
    public function __construct(array $options, FilesInterface $files = null)
    {
        parent::__construct($options, $files);
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->database = null;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(BucketInterface $bucket, string $name): bool
    {
        return $this->gridFS($bucket)->findOne(['filename' => $name]) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function size(BucketInterface $bucket, string $name): ?int
    {
        if (!$this->exists($bucket, $name)) {
            return null;
        }

        return $this->gridFS($bucket)->findOne(['filename' => $name])->length;
    }

    /**
     * {@inheritdoc}
     */
    public function put(BucketInterface $bucket, string $name, StreamInterface $stream): bool
    {
        //No updates, only delete and re-upload
        if ($this->exists($bucket, $name)) {
            $this->delete($bucket, $name);
        }

        $resource = StreamWrapper::getResource($stream);
        try {
            $result = $this->gridFS($bucket)->uploadFromStream($name, $resource);
        } finally {
            StreamWrapper::release($resource);
            fclose($resource);
        }

        if (empty($result)) {
            throw new ServerException("Unable to store {$name} at GridFS server");
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * This method must be improved in future versions!
     *
     * @see https://github.com/mongodb/mongo-php-library/issues/317
     * @see https://github.com/slimphp/Slim/issues/2112
     * @see https://jira.mongodb.org/browse/PHPLIB-213
     */
    public function getStream(BucketInterface $bucket, string $name): StreamInterface
    {
        $file = $this->gridFS($bucket)->findOne(['filename' => $name]);
        if (empty($file)) {
            throw new ServerException(
                "Unable to create stream for '{$name}', object does not exists"
            );
        }

        //We have to use temporary file now due to issues with seeking (should be fixed soon)
        $resource = fopen('php://memory', 'rw');

        //Copying from non seekable to seekable stream
        stream_copy_to_stream($this->gridFS($bucket)->openDownloadStream($file->_id), $resource);

        //Working thought the memory, WILL FAIL on very huge files
        rewind($resource);

        //Ugly :/
        return stream_for($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(BucketInterface $bucket, string $name)
    {
        if (!$this->exists($bucket, $name)) {
            throw new ServerException("Unable to delete object, file not found");
        }

        $file = $this->gridFS($bucket)->findOne(['filename' => $name]);
        $this->gridFS($bucket)->delete($file->_id);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(BucketInterface $bucket, string $oldName, string $newName): bool
    {
        $file = $this->gridFS($bucket)->findOne(['filename' => $oldName]);

        if (empty($file)) {
            return false;
        }

        $this->gridFS($bucket)->rename($file->_id, $newName);

        return true;
    }

    /**
     * Get valid GridFS collection associated with bucket.
     *
     * @param BucketInterface $bucket Bucket instance.
     *
     * @return Bucket
     */
    protected function gridFS(BucketInterface $bucket): Bucket
    {
        if (empty($this->database)) {
            $this->database = new Database(
                new Manager($this->options['connection']),
                $this->options['database']
            );
        }

        return $this->database->selectGridFSBucket([
            'bucketName' => $bucket->getOption('bucket')
        ]);
    }
}