<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Enum;

abstract class AdapterName
{
    public const LOCAL = 'local';
    public const AWS_S3 = 'awsS3';
    public const ASYNC_AWS_S3 = 'asyncAwsS3';
    public const FTP = 'ftp';
    public const SFTP = 'sftp';
    public const MEMORY = 'memory';

    public const ALL = [
        self::LOCAL,
        self::AWS_S3,
        self::ASYNC_AWS_S3,
        self::FTP,
        self::SFTP,
        self::MEMORY,
    ];
}
