Storage Engine. 
========

AwsS3 file server
-------
To work with local file server you should use one of specific adapters:
- `\League\Flysystem\AwsS3V3\AwsS3V3Adapter`
  * `composer require league/flysystem-aws-s3-v3` for adapter installation
- `\League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter`
  * `composer require league/flysystem-async-aws-s3` for adapter installation

## Required options
- `bucket` - used bucket name
- `client` - S3Client with options

## Additional options
- `path-prefix` - optional path prefix
- `visibility` - `public` or `private`
- `url-expires` - string or DateTimeInterface object with expires term for urls built by resolver

## Example config file for basic usage
```php
<?php
return [
    'servers' => [
        'aws' => [
            'adapter' => \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class,
            'options' => [
                'bucket' => env('AWS_BUCKET'),
                'client' => [
                    'class' => \Aws\S3\S3Client::class,
                    'options' => [
                        'version' => 'latest',
                        'region' => env('AWS_REGION'),
                        'credentials' => new \Aws\Credentials\Credentials(env('AWS_KEY'), env('AWS_SECRET')),
                        'use_path_style_endpoint' => true,
                        'endpoint' => env('AWS_PUBLIC_URL')
                    ],
                ],
            ]
        ],
    ],
];
```

## Example config file for advanced usage
```php
<?php
return [
    'servers' => [
        'aws' => [
            'adapter' => \League\Flysystem\AwsS3V3\AwsS3V3Adapter::class,
            'options' => [
                'bucket' => env('AWS_BUCKET'),
                'client' => [
                    'class' => \Aws\S3\S3Client::class,
                    'options' => [
                        'version' => 'latest',
                        'region' => env('AWS_REGION'),
                        'credentials' => new \Aws\Credentials\Credentials(env('AWS_KEY'), env('AWS_SECRET')),
                        'use_path_style_endpoint' => true,
                        'endpoint' => env('AWS_PUBLIC_URL'),
                        'url-expires' => '+48hours',
                    ],
                ],
                'path-prefix' => '/some/prefixDir/',
                'visibility' => 'public',
            ]
        ],
    ],
];
```
