<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Storage\Server;

use Psr\Http\Message\StreamInterface;
use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exception\ServerException;
use Spiral\Streams\StreamWrapper;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Provides abstraction level to work with data located at remove SFTP server.
 */
final class SftpServer extends AbstractServer
{
    /**
     * Authorization methods.
     */
    public const NONE     = 'none';
    public const PASSWORD = 'password';
    public const PUB_KEY  = 'pubkey';

    /**
     * @var array
     */
    protected $options = [
        'host'       => '',
        'methods'    => [],
        'port'       => 22,
        'home'       => '/',

        //Authorization method and username
        'authMethod' => 'password',
        'username'   => '',

        //Used with "password" authorization
        'password'   => '',

        //User with "pubkey" authorization
        'publicKey'  => '',
        'privateKey' => '',
        'secret'     => null
    ];

    /** @var resource */
    protected $conn = null;

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->conn = null;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(BucketInterface $bucket, string $name): bool
    {
        $this->connect();

        return file_exists($this->castRemoteFilename($bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function size(BucketInterface $bucket, string $name): ?int
    {
        $this->connect();
        if (!$this->exists($bucket, $name)) {
            return null;
        }

        return filesize($this->castRemoteFilename($bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function put(BucketInterface $bucket, string $name, StreamInterface $stream): bool
    {
        $this->connect();

        $stream->rewind();

        $expectedSize = $stream->getSize();


        //Make sure target directory exists
        $this->ensureLocation($bucket, $name);

        //Remote file
        $destination = fopen($this->castRemoteFilename($bucket, $name), 'w');

        $resource = StreamWrapper::getResource($stream);
        try {
            $size = stream_copy_to_stream($resource, $destination);
        } finally {
            StreamWrapper::release($resource);
            fclose($resource);
            fclose($destination);
        }

        return $expectedSize == $size && $this->refreshPermissions($bucket, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(BucketInterface $bucket, string $name): StreamInterface
    {
        $this->connect();

        //Thought native sftp resource
        return stream_for(fopen($this->castRemoteFilename($bucket, $name), 'rb'));
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

        ssh2_sftp_unlink($this->conn, $path = $this->castPath($bucket, $name));

        //Cleaning file cache for removed file
        clearstatcache(false, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(BucketInterface $bucket, string $oldName, string $newName): bool
    {
        $this->connect();

        if (!$this->exists($bucket, $oldName)) {
            throw new ServerException(
                "Unable to rename storage object '{$oldName}', object does not exists at SFTP server"
            );
        }

        $location = $this->ensureLocation($bucket, $newName);
        if (file_exists($this->castRemoteFilename($bucket, $newName))) {
            //We have to clean location before renaming
            $this->delete($bucket, $newName);
        }

        if (!ssh2_sftp_rename($this->conn, $this->castPath($bucket, $oldName), $location)) {
            throw new ServerException(
                "Unable to rename storage object '{$oldName}' to '{$newName}'"
            );
        }

        return $this->refreshPermissions($bucket, $newName);
    }

    /**
     * Ensure that SSH connection is up and can be used for file operations.
     *
     * @throws ServerException
     */
    protected function connect()
    {
        if (!empty($this->conn)) {
            return;
        }

        if (!extension_loaded('ssh2')) {
            throw new ServerException(
                "Unable to initialize sftp storage server, extension 'ssh2' not found"
            );
        }

        $session = ssh2_connect(
            $this->options['host'],
            $this->options['port'],
            $this->options['methods']
        );

        if (empty($session)) {
            throw new ServerException(
                "Unable to connect to remote SSH server '{$this->options['host']}'"
            );
        }

        //Authorization
        switch ($this->options['authMethod']) {
            case self::NONE:
                ssh2_auth_none($session, $this->options['username']);
                break;

            case self::PASSWORD:
                ssh2_auth_password(
                    $session,
                    $this->options['username'],
                    $this->options['password']
                );
                break;

            case self::PUB_KEY:
                ssh2_auth_pubkey_file(
                    $session,
                    $this->options['username'],
                    $this->options['publicKey'],
                    $this->options['privateKey'],
                    $this->options['secret']
                );
                break;
        }

        $this->conn = ssh2_sftp($session);
    }

    /**
     * Get ssh2 specific uri which can be used in default php functions. Assigned to ssh2.sftp
     * stream wrapper.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return string
     */
    protected function castRemoteFilename(BucketInterface $bucket, string $name): string
    {
        return 'ssh2.sftp://' . $this->conn . $this->castPath($bucket, $name);
    }

    /**
     * Get full file location on server including homedir.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return string
     */
    protected function castPath(BucketInterface $bucket, string $name): string
    {
        return $this->files->normalizePath(
            $this->options['home'] . '/' . $bucket->getOption('directory') . '/' . $name
        );
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
        $directory = dirname($this->castPath($bucket, $name));

        $mode = $bucket->getOption('mode', FilesInterface::RUNTIME);
        if (file_exists('ssh2.sftp://' . $this->conn . $directory)) {
            if (function_exists('ssh2_sftp_chmod')) {
                ssh2_sftp_chmod($this->conn, $directory, $mode | 0111);
            }

            return $this->castPath($bucket, $name);
        }

        $directories = explode('/', substr($directory, strlen($this->options['home'])));

        $location = $this->options['home'];
        foreach ($directories as $directory) {
            if (!$directory) {
                continue;
            }

            $location .= '/' . $directory;

            if (!file_exists('ssh2.sftp://' . $this->conn . $location)) {
                if (!ssh2_sftp_mkdir($this->conn, $location)) {
                    throw new ServerException(
                        "Unable to create directory {$location} using sftp connection"
                    );
                }

                if (function_exists('ssh2_sftp_chmod')) {
                    ssh2_sftp_chmod($this->conn, $directory, $mode | 0111);
                }
            }
        }

        return $this->castPath($bucket, $name);
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
        if (!function_exists('ssh2_sftp_chmod')) {
            return true;
        }

        return ssh2_sftp_chmod(
            $this->conn,
            $this->castPath($bucket, $name),
            $bucket->getOption('mode', FilesInterface::RUNTIME)
        );
    }
}