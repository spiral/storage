<?php declare(strict_types=1);
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
use GuzzleHttp\Exception\GuzzleException;
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
        'server'  => 'https://s3.amazonaws.com',
        'timeout' => 240,
        'key'     => '',
        'secret'  => ''
    ];

    /** @var ClientInterface */
    protected $client = null;

    /**
     * @param array               $options
     * @param FilesInterface|null $files
     */
    public function __construct(array $options, FilesInterface $files = null)
    {
        parent::__construct($options, $files);
        $this->client = new Client($this->options);
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
    }

    /**
     * Version of driver with alternative client being set up.
     *
     * @param ClientInterface $client
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
     */
    public function exists(
        BucketInterface $bucket,
        string $name,
        ResponseInterface &$response = null
    ): bool {
        $response = $this->run($this->buildRequest('HEAD', $bucket, $name), [404]);
        if (empty($response) || $response->getStatusCode() !== 200) {
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

        /** @var ResponseInterface $response */
        return (int)$response->getHeaderLine('Content-Length');
    }

    /**
     * {@inheritdoc}
     */
    public function put(BucketInterface $bucket, string $name, StreamInterface $stream): bool
    {
        if (empty($mimetype = mimetype_from_filename($name))) {
            $mimetype = self::DEFAULT_MIMETYPE;
        }

        $request = $this->buildRequest(
            'PUT',
            $bucket,
            $name,
            $this->createHeaders($bucket, $name, $stream),
            [
                'Acl'          => $bucket->getOption('public') ? 'public-read' : 'private',
                'Content-Type' => $mimetype
            ]
        );

        $stream->rewind();

        return $this->run($request->withBody($stream)) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(BucketInterface $bucket, string $name): StreamInterface
    {
        return $this->run($this->buildRequest('GET', $bucket, $name))->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(BucketInterface $bucket, string $name)
    {
        if (!$this->exists($bucket, $name)) {
            throw new ServerException("Unable to delete object, file not found");
        }

        $this->run($this->buildRequest('DELETE', $bucket, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function rename(BucketInterface $bucket, string $oldName, string $newName): bool
    {
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

        $this->run($request);
        $this->delete($bucket, $oldName);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copy(BucketInterface $bucket, BucketInterface $destination, string $name): bool
    {
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

        $this->run($request);

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
     * Wrap guzzle errors into
     *
     * @param RequestInterface $request
     * @param array            $skipCodes Method should return null if code matched.
     * @return ResponseInterface
     */
    private function run(RequestInterface $request, array $skipCodes = []): ?ResponseInterface
    {
        try {
            return $this->client->send($request);
        } catch (GuzzleException $e) {
            if (in_array($e->getCode(), $skipCodes)) {
                return null;
            }

            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }
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
            'AWS ' . $this->options['key'] . ':' . base64_encode(hash_hmac(
                'sha1',
                join("\n", $signature),
                $this->options['secret'],
                true
            ))
        );
    }

    /**
     * Generate object headers.
     *
     * @param BucketInterface $bucket
     * @param string          $name
     * @param StreamInterface $stream
     *
     * @return array
     */
    private function createHeaders(BucketInterface $bucket, string $name, StreamInterface $stream): array
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

        $stream->rewind();

        return $headers + [
                'Content-MD5'  => base64_encode(md5($stream->__toString(), true)),
                'Content-Type' => $mimetype
            ];
    }
} 