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
        return $this->serverInfo->getClient()
                ->getObjectUrl(
                    $this->serverInfo->getOption(AwsS3Info::BUCKET_NAME),
                    $filePath
                );
    }
}
