<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Resolver;

use Spiral\StorageEngine\Config\DTO\ServerInfo\Aws\AwsS3Info;
use Spiral\StorageEngine\Config\DTO\ServerInfo\ServerInfoInterface;

class AwsS3Resolver extends AbstractResolver
{
    protected const SERVER_INFO_CLASS = AwsS3Info::class;

    /**
     * @var ServerInfoInterface|AwsS3Info
     */
    protected ServerInfoInterface $serverInfo;

    public function buildUrl(string $filePath): ?string
    {
        $s3Client = $this->serverInfo->getClient();

        return  (string)$s3Client
            ->createPresignedRequest(
                $s3Client->getCommand(
                    'GetObject',
                    [
                        'Bucket' => $this->serverInfo->getOption(AwsS3Info::BUCKET),
                        'Key' => $this->normalizePathForServer($filePath),
                    ]
                ),
                $this->serverInfo->getUrlExpires()
            )
            ->getUri();
    }
}
