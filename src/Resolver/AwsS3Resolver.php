<?php

declare(strict_types=1);

namespace Spiral\Storage\Resolver;

use Spiral\Storage\Config\DTO\FileSystemInfo\Aws\AwsS3Info;
use Spiral\Storage\Config\DTO\FileSystemInfo\FileSystemInfoInterface;

class AwsS3Resolver extends AbstractAdapterResolver
{
    public const EXPIRES_OPTION = 'expires';

    protected const FILE_SYSTEM_INFO_CLASS = AwsS3Info::class;

    private const DEFAULT_URL_EXPIRES = '+24hours';

    /**
     * @var FileSystemInfoInterface|AwsS3Info
     */
    protected FileSystemInfoInterface $fsInfo;

    /**
     * @inheritDoc
     */
    public function buildUrl(string $uri, array $options = []): ?string
    {
        $s3Client = $this->fsInfo->getClient();

        return (string)$s3Client
            ->createPresignedRequest(
                $s3Client->getCommand(
                    'GetObject',
                    [
                        'Bucket' => $this->fsInfo->getOption(AwsS3Info::BUCKET_KEY),
                        'Key' => $this->normalizeFilePath($uri),
                    ]
                ),
                array_key_exists(static::EXPIRES_OPTION, $options)
                    ? $options[static::EXPIRES_OPTION]
                    : static::DEFAULT_URL_EXPIRES
            )
            ->getUri();
    }
}
