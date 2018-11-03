<?php
/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @package   spiralFramework
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2011
 */

namespace Spiral\Storage\Server;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Spiral\Files\FilesInterface;
use Spiral\Storage\BucketInterface;
use Spiral\Storage\Exception\ServerException;
use function GuzzleHttp\Psr7\mimetype_from_filename;

/**
 * Provides abstraction level to work with data located in Amazon S3 cloud.
 */
class AmazonServer extends AbstractServer
{
    /**
     * @invisible
     * @var array
     */
    protected $options = [
        'server'    => 'https://s3.amazonaws.com',
        'timeout'   => 0,
        'accessKey' => '',
        'secretKey' => ''
    ];

    /**
     * @invisible
     * @var ClientInterface
     */
    protected $client = null;

    /**
     * @param array               $options
     * @param FilesInterface|null $files
     */
    public function __construct(array $options, FilesInterface $files)
    {
        parent::__construct($options, $files);
        $this->client = new Client($this->options);
    }

    /**
     * Version of driver with alternative client being set up.
     *
     * @param ClientInterface $client
     *
     * @return self
     */
    public function withClient(ClientInterface $client): AmazonServer
    {
        $server = clone $this;
        $server->client = $client;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param ResponseInterface $response Reference.
     *
     * @return bool|ResponseInterface
     */
    public function exists(
        BucketInterface $bucket,
        string $name,
        ResponseInterface &$response = null
    ): bool {
        try {
            $response = $this->client->send($this->buildRequest(
                'HEAD',
                $bucket,
                $name
            ));
        } catch (ClientException $e) {
            if ($e->getCode() == 404) {
                return false;
            }

            //Something wrong with connection
            throw $e;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function size(BucketInterface $bucket, string $name): ?int
    {
        if (!$this->exists($bucket, $name, $response)) {
            return null;
        }

        /**
         * @var ResponseInterface $response
         */
        return (int)$response->getHeaderLine('Content-Length');
    }

    /**
     * {@inheritdoc}
     */
    public function put(BucketInterface $bucket, string $name, $source): bool
    {
        if (empty($mimetype = mimetype_from_filename($name))) {
            $mimetype = self::DEFAULT_MIMETYPE;
        }

        $request = $this->buildRequest(
            'PUT',
            $bucket,
            $name,
            $this->createHeaders($bucket, $name, $source),
            [
                'Acl'          => $bucket->getOption('public') ? 'public-read' : 'private',
                'Content-Type' => $mimetype
            ]
        );

        $response = $this->client->send($request->withBody($this->castStream($source)));
        if ($response->getStatusCode() != 200) {
            throw new ServerException("Unable to put '{$name}' to Amazon server");
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function allocateStream(BucketInterface $bucket, string $name): StreamInterface
    {
        try {
            $response = $this->client->send($this->buildRequest('GET', $bucket, $name));
        } catch (ClientException $e) {
            if ($e->getCode() != 404) {
                //Some authorization or other error
                throw $e;
            }

            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        return $response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(BucketInterface $bucket, string $name)
    {
        if (!$this->exists($bucket, $name)) {
            throw new ServerException("Unable to delete object, file not found");
        }

        $this->client->send($this->buildRequest('DELETE', $bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function rename(BucketInterface $bucket, string $oldName, string $newName): bool
    {
        try {
            $request = $this->buildRequest(
                'PUT',
                $bucket,
                $newName,
                [],
                [
                    'Acl'         => $bucket->getOption('public') ? 'public-read' : 'private',
                    'Copy-Source' => $this->buildUri($bucket, $oldName)->getPath()
                ]
            );

            $this->client->send($request);
        } catch (ClientException $e) {
            if ($e->getCode() != 404) {
                //Some authorization or other error
                throw $e;
            }

            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        $this->delete($bucket, $oldName);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copy(BucketInterface $bucket, BucketInterface $destination, string $name): bool
    {
        try {
            $request = $this->buildRequest(
                'PUT',
                $destination,
                $name,
                [],
                [
                    'Acl'         => $destination->getOption('public') ? 'public-read' : 'private',
                    'Copy-Source' => $this->buildUri($bucket, $name)->getPath()
                ]
            );

            $this->client->send($request);
        } catch (ClientException $e) {
            if ($e->getCode() != 404) {
                //Some authorization or other error
                throw $e;
            }

            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        return true;
    }

    /**
     * Create instance of UriInterface based on provided bucket options and storage object name.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     *
     * @return UriInterface
     */
    protected function buildUri(BucketInterface $bucket, string $name): UriInterface
    {
        return new Uri(
            $this->options['server'] . '/' . $bucket->getOption('bucket') . '/' . rawurlencode($name)
        );
    }

    /**
     * Helper to create configured PSR7 request with set of amazon commands.
     *
     * @param string          $method
     * @param BucketInterface $bucket
     * @param string          $name
     * @param array           $headers
     * @param array           $commands Amazon commands associated with values.
     *
     * @return RequestInterface
     */
    protected function buildRequest(
        string $method,
        BucketInterface $bucket,
        string $name,
        array $headers = [],
        array $commands = []
    ): RequestInterface {
        $headers += [
            'Date'         => gmdate('D, d M Y H:i:s T'),
            'Content-MD5'  => '',
            'Content-Type' => ''
        ];

        $packedCommands = $this->packCommands($commands);

        return $this->signRequest(
            new Request($method, $this->buildUri($bucket, $name), $headers + $packedCommands),
            $packedCommands
        );
    }

    /**
     * Generate request headers based on provided set of amazon commands.
     *
     * @param array $commands
     *
     * @return array
     */
    private function packCommands(array $commands): array
    {
        $headers = [];
        foreach ($commands as $command => $value) {
            $headers['X-Amz-' . $command] = $value;
        }

        return $headers;
    }

    /**
     * Sign amazon request.
     *
     * @param RequestInterface $request
     * @param array            $packedCommands Headers generated based on request commands, see
     *                                         packCommands() method for more information.
     *
     * @return RequestInterface
     */
    private function signRequest(
        RequestInterface $request,
        array $packedCommands = []
    ): RequestInterface {
        $signature = [
            $request->getMethod(),
            $request->getHeaderLine('Content-MD5'),
            $request->getHeaderLine('Content-Type'),
            $request->getHeaderLine('Date')
        ];

        $normalizedCommands = [];
        foreach ($packedCommands as $command => $value) {
            if (!empty($value)) {
                $normalizedCommands[] = strtolower($command) . ':' . $value;
            }
        }

        if (!empty($normalizedCommands)) {
            sort($normalizedCommands);
            $signature[] = join("\n", $normalizedCommands);
        }

        $signature[] = $request->getUri()->getPath();

        return $request->withAddedHeader(
            'Authorization',
            'AWS ' . $this->options['accessKey'] . ':' . base64_encode(hash_hmac(
                'sha1',
                join("\n", $signature),
                $this->options['secretKey'],
                true
            ))
        );
    }

    /**
     * Generate object headers.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     * @param mixed           $source
     *
     * @return array
     */
    private function createHeaders(BucketInterface $bucket, string $name, $source): array
    {
        if (empty($mimetype = mimetype_from_filename($name))) {
            $mimetype = self::DEFAULT_MIMETYPE;
        };

        //Possible to add custom headers into the bucket
        $headers = $bucket->getOption('headers', []);

        if (!empty($maxAge = $bucket->getOption('maxAge', 0))) {
            //Shortcut
            $headers['Cache-control'] = 'max-age=' . $bucket->getOption('maxAge', 0) . ', public';
            $headers['Expires'] = gmdate(
                'D, d M Y H:i:s T',
                time() + $bucket->getOption('maxAge', 0)
            );
        }

        return $headers + [
                'Content-MD5'  => base64_encode(md5_file($this->castFilename($source), true)),
                'Content-Type' => $mimetype
            ];
    }
} 