<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Storage\Server;

use MongoDB\Database;
use MongoDB\GridFS\Bucket;
use Psr\Http\Message\StreamInterface;
use Spiral\Files\FilesInterface;
use Spiral\Streams\StreamWrapper;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exception\ServerException;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Provides abstraction level to work with data located in GridFS storage.
 */
class GridFSServer extends AbstractServer
{
    /** @var Database */
    protected $database;

    /**
     * @param Database            $database
     * @param FilesInterface|null $files
     */
    public function __construct(Database $database, FilesInterface $files)
    {
        parent::__construct([], $files);
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|\MongoGridFSFile
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
    public function put(BucketInterface $bucket, string $name, $source): bool
    {
        //No updates, only delete and re-upload
        if ($this->exists($bucket, $name)) {
            $this->delete($bucket, $name);
        }

        $result = $this->gridFS($bucket)->uploadFromStream(
            $name,
            StreamWrapper::getResource($this->castStream($source))
        );

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
    public function allocateStream(BucketInterface $bucket, string $name): StreamInterface
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
        return $this->database->selectGridFSBucket(['bucketName' => $bucket->getOption('bucket')]);
    }
}