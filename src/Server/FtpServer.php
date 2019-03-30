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
 * Provides abstraction level to work with data located at remove FTP server.
 */
class FtpServer extends AbstractServer
{
    /**
     * @var array
     */
    protected $options = [
        'host'     => '',
        'port'     => 21,
        'timeout'  => 60,
        'username' => '',
        'password' => '',
        'home'     => '/',
        'passive'  => true,
        'chmod'    => false
    ];

    /**  @var resource */
    protected $conn = null;

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if (!empty($this->conn)) {
            ftp_close($this->conn);
            $this->conn = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(BucketInterface $bucket, string $name): bool
    {
        $this->connect();

        return ftp_size($this->conn, $this->getPath($bucket, $name)) !== -1;
    }

    /**
     * {@inheritdoc}
     */
    public function size(BucketInterface $bucket, string $name): ?int
    {
        $this->connect();

        if (($size = ftp_size($this->conn, $this->getPath($bucket, $name))) != -1) {
            return $size;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function put(BucketInterface $bucket, string $name, StreamInterface $stream): bool
    {
        $this->connect();

        $location = $this->ensureLocation($bucket, $name);

        $filename = StreamWrapper::localFilename($stream);
        try {
            if (!ftp_put($this->conn, $location, $filename, FTP_BINARY)) {
                throw new ServerException("Unable to put '{$name}' to FTP server");
            }
        } finally {
            StreamWrapper::releaseUri($filename);
        }

        return $this->refreshPermissions($bucket, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(BucketInterface $bucket, string $name): StreamInterface
    {
        $this->connect();

        if (!$filename = $this->localFilename($bucket, $name)) {
            throw new ServerException(
                "Unable to create stream for '{$name}', object does not exists"
            );
        }

        //Thought local file
        return stream_for(fopen($filename, 'rb'));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(BucketInterface $bucket, string $name)
    {
        $this->connect();

        if (!$this->exists($bucket, $name)) {
            throw new ServerException("Unable to delete object, file not found");
        }

        ftp_delete($this->conn, $this->getPath($bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function rename(BucketInterface $bucket, string $oldName, string $newName): bool
    {
        $this->connect();

        if (!$this->exists($bucket, $oldName)) {
            throw new ServerException("Unable to rename '{$oldName}', object does not exists");
        }

        $location = $this->ensureLocation($bucket, $newName);
        try {
            if (!ftp_rename($this->conn, $this->getPath($bucket, $oldName), $location)) {
                throw new \ErrorException("Unable to rename '{$oldName}' to '{$newName}'.");
            }
        } catch (\Throwable $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->refreshPermissions($bucket, $newName);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(
        BucketInterface $bucket,
        BucketInterface $destination,
        string $name
    ): bool {
        $this->connect();

        if (!$this->exists($bucket, $name)) {
            throw new ServerException("Unable to replace '{$name}', object does not exists");
        }

        $location = $this->ensureLocation($destination, $name);
        try {
            if (!ftp_rename($this->conn, $this->getPath($bucket, $name), $location)) {
                throw new \ErrorException("Unable to replace '{$name}'.");
            }
        } catch (\Throwable $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->refreshPermissions($bucket, $name);
    }

    /**
     * Ensure FTP connection.
     *
     * @throws ServerException
     */
    protected function connect()
    {
        if (!empty($this->conn)) {
            return;
        }

        if (!extension_loaded('ftp')) {
            throw new ServerException(
                "Unable to initialize ftp storage server, extension 'ftp' not found"
            );
        }

        $conn = ftp_connect(
            $this->options['host'],
            $this->options['port'],
            $this->options['timeout']
        );

        if (empty($conn)) {
            throw new ServerException(
                "Unable to connect to remote FTP server '{$this->options['host']}'"
            );
        }

        if (!ftp_login($conn, $this->options['username'], $this->options['password'])) {
            throw new ServerException(
                "Unable to authorize on remote FTP server '{$this->options['host']}'"
            );
        }

        if (!ftp_pasv($conn, $this->options['passive'])) {
            throw new ServerException(
                "Unable to set passive mode at remote FTP server '{$this->options['host']}'"
            );
        }

        $this->conn = $conn;
    }

    /**
     * Ensure that target directory exists and has right permissions.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return string
     * @throws ServerException
     */
    protected function ensureLocation(BucketInterface $bucket, string $name): string
    {
        $directory = dirname($this->getPath($bucket, $name));
        $mode = $bucket->getOption('mode', FilesInterface::RUNTIME);

        try {
            if (ftp_chdir($this->conn, $directory)) {
                ftp_chmod($this->conn, $mode | 0111, $directory);

                return $this->getPath($bucket, $name);
            }
        } catch (\Exception $e) {
            //Directory has to be created
        }

        ftp_chdir($this->conn, $this->options['home']);

        $directories = explode('/', substr($directory, strlen($this->options['home'])));
        foreach ($directories as $directory) {
            if (!$directory) {
                continue;
            }

            try {
                ftp_chdir($this->conn, $directory);
            } catch (\Exception $e) {
                ftp_mkdir($this->conn, $directory);
                ftp_chmod($this->conn, $mode | 0111, $directory);
                ftp_chdir($this->conn, $directory);
            }
        }

        return $this->getPath($bucket, $name);
    }

    /**
     * {@inheritdoc}
     */
    protected function localFilename(BucketInterface $bucket, string $name): string
    {
        $this->connect();

        if (!$this->exists($bucket, $name)) {
            throw new ServerException(
                "Unable to create local filename for '{$name}', object does not exists"
            );
        }

        //File should be removed after processing
        $tempFilename = $this->files->tempFilename($this->files->extension($name));

        if (!ftp_get(
            $this->conn,
            $tempFilename,
            $this->getPath($bucket, $name),
            FTP_BINARY
        )) {
            throw new ServerException("Unable to create local filename for '{$name}'");
        }

        return $tempFilename;
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
        return $this->files->normalizePath(
            $this->options['home'] . '/' . $bucket->getOption('directory') . $name
        );
    }

    /**
     * Refresh file permissions accordingly to container options.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return bool
     */
    protected function refreshPermissions(BucketInterface $bucket, string $name): bool
    {
        if (!$this->options['chmod']) {
            //No CHMOD altering
            return true;
        }

        $mode = $bucket->getOption('mode', FilesInterface::RUNTIME);

        return ftp_chmod($this->conn, $mode, $this->getPath($bucket, $name)) !== false;
    }
}