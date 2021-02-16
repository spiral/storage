# Storage Engine

## AwsS3 file server
To work with local file server you should use one of specific adapters:
- `\League\Flysystem\AwsS3V3\AwsS3V3Adapter`
  * `composer require league/flysystem-aws-s3-v3` for adapter installation
- `\League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter`
  * `composer require league/flysystem-async-aws-s3` for adapter installation

### Required options
- `client` - S3Client 

### Additional options
- `path-prefix` - optional path prefix
- `visibility` - `public` or `private`
- `resolver` - specific adapter resolver for handling url. Resolver must implements `\Spiral\StorageEngine\Resolver\AdapterResolverInterface`

### Example config file for basic usage
```php
<?php

return [
    'servers' => [
        'awsS3V3Server' => [
            'adapter' => \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class,
            'options' => [
                'client' => new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => env('AWS_REGION'),
                    'credentials' => new \Aws\Credentials\Credentials(env('AWS_KEY'), env('AWS_SECRET')),
                    'use_path_style_endpoint' => true,
                    'endpoint' => env('AWS_PUBLIC_URL')
                ]),
            ]
        ],
    ],
    'buckets' => [
        'aws' => [
            'server' => 'awsS3V3Server',
            'options' => [
                'bucket' => env('AWS_BUCKET'),
            ],
        ],
    ],
];
```

### Example config file for advanced usage
```php
<?php

return [
    'servers' => [
        'awsS3V3Server' => [
            'adapter' => \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class,
            'options' => [
                'client' => new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => env('AWS_REGION'),
                    'credentials' => new \Aws\Credentials\Credentials(env('AWS_KEY'), env('AWS_SECRET')),
                    'use_path_style_endpoint' => true,
                    'endpoint' => env('AWS_PUBLIC_URL')
                ]),
                'path-prefix' => '/some/prefixDir/',
                'visibility' => 'public',
            ]
        ],
    ],    
    'buckets' => [
        'aws' => [
            'server' => 'awsS3V3Server',
            'options' => [
                'bucket' => env('AWS_BUCKET'),
            ],
        ],
    ],
];
```
