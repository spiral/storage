<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;

class AwsS3Resolver extends AbstractResolver
{
    public const EXPIRES_OPTION = 'expires';

    protected const SERVER_INFO_CLASS = AwsS3Info::class;

    private const DEFAULT_URL_EXPIRES = '+24hours';

    /**
     * @var ServerInfoInterface|AwsS3Info
     */
    protected ServerInfoInterface $serverInfo;

    public function buildUrl(string $uri, array $options = []): ?string
    {
        $s3Client = $this->serverInfo->getClient();

        return (string)$s3Client
            ->createPresignedRequest(
                $s3Client->getCommand(
                    'GetObject',
                    [
                        'Bucket' => $this->serverInfo->getOption(AwsS3Info::BUCKET_KEY),
                        'Key' => $this->normalizeFilePathToUri($uri),
                    ]
                ),
                array_key_exists(static::EXPIRES_OPTION, $options)
                    ? $options[static::EXPIRES_OPTION]
                    : static::DEFAULT_URL_EXPIRES
            )
            ->getUri();
    }
}
