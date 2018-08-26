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
use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exceptions\ServerException;
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
        'login'    => '',
        'password' => '',
        'home'     => '/',
        'passive'  => true,
        'chmod'    => false
    ];

    /**
     * FTP Connection.
     *
     * @var resource
     */
    protected $connection = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $options, FilesInterface $files)
    {
        parent::__construct($options, $files);

        if (!extension_loaded('ftp')) {
            throw new ServerException(
                "Unable to initialize ftp storage server, extension 'ftp' not found"
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(BucketInterface $bucket, string $name): bool
    {
        $this->connect();

        return ftp_size($this->connection, $this->getPath($bucket, $name)) != -1;
    }

    /**
     * {@inheritdoc}
     */
    public function size(BucketInterface $bucket, string $name): ?int
    {
        $this->connect();
        if (($size = ftp_size($this->connection, $this->getPath($bucket, $name))) != -1) {
            return $size;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function put(BucketInterface $bucket, string $name, $source): bool
    {
        $this->connect();
        $location = $this->ensureLocation($bucket, $name);
        if (!ftp_put($this->connection, $location, $this->castFilename($source), FTP_BINARY)) {
            throw new ServerException("Unable to put '{$name}' to FTP server");
        }

        return $this->refreshPermissions($bucket, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function allocateFilename(BucketInterface $bucket, string $name): string
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
            $this->connection,
            $tempFilename,
            $this->getPath($bucket, $name),
            FTP_BINARY
        )) {
            throw new ServerException("Unable to create local filename for '{$name}'");
        }

        return $tempFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function allocateStream(BucketInterface $bucket, string $name): StreamInterface
    {
        $this->connect();
        if (!$filename = $this->allocateFilename($bucket, $name)) {
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

        ftp_delete($this->connection, $this->getPath($bucket, $name));
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
        if (!ftp_rename($this->connection, $this->getPath($bucket, $oldName), $location)) {
            throw new ServerException("Unable to rename '{$oldName}' to '{$newName}'.");
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
        if (!ftp_rename($this->connection, $this->getPath($bucket, $name), $location)) {
            throw new ServerException("Unable to replace '{$name}'.");
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
        if (!empty($this->connection)) {
            return;
        }

        $connection = ftp_connect(
            $this->options['host'],
            $this->options['port'],
            $this->options['timeout']
        );

        if (empty($this->connection)) {
            throw new ServerException(
                "Unable to connect to remote FTP server '{$this->options['host']}'"
            );
        }

        if (!ftp_login($this->connection, $this->options['login'], $this->options['password'])) {
            throw new ServerException(
                "Unable to connect to remote FTP server '{$this->options['host']}'"
            );
        }

        if (!ftp_pasv($this->connection, $this->options['passive'])) {
            throw new ServerException(
                "Unable to set passive mode at remote FTP server '{$this->options['host']}'"
            );
        }

        $this->connection = $connection;
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
            if (ftp_chdir($this->connection, $directory)) {
                ftp_chmod($this->connection, $mode | 0111, $directory);

                return $this->getPath($bucket, $name);
            }
        } catch (\Exception $e) {
            //Directory has to be created
        }

        ftp_chdir($this->connection, $this->options['home']);

        $directories = explode('/', substr($directory, strlen($this->options['home'])));
        foreach ($directories as $directory) {
            if (!$directory) {
                continue;
            }

            try {
                ftp_chdir($this->connection, $directory);
            } catch (\Exception $e) {
                ftp_mkdir($this->connection, $directory);
                ftp_chmod($this->connection, $mode | 0111, $directory);
                ftp_chdir($this->connection, $directory);
            }
        }

        return $this->getPath($bucket, $name);
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

        return ftp_chmod($this->connection, $mode, $this->getPath($bucket, $name)) !== false;
    }

    /**
     * Drop FTP connection.
     */
    public function __destruct()
    {
        if (!empty($this->connection)) {
            ftp_close($this->connection);
            $this->connection = null;
        }
    }
}