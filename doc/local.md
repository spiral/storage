Storage Engine. 
========

Local file server
-------
To work with local file server you don't need any additional adapters. 
You can use built-in local adapter `\League\Flysystem\Local\LocalFilesystemAdapter`

## Required options
- `rootDir` - directory of local server for file storage
- `host` - host for urls building via resolvers by filepath

## Additional options
Any of the additional options can be used to configure specific params
- `visibility` - customize how visibility is converted to unix permissions
  * allows to define what access responsible to detect which directory/file is public or private
- `write-flags` - write flags. LOCK_EX by default.
- `link-handling` - How to deal with links, either LocalFilesystemAdapter::DISALLOW_LINKS or LocalFilesystemAdapter::SKIP_LINKS
  * Disallowing them causes exceptions when encountered

## Example config file for basic usage
```php
<?php
return [
    'servers' => [
        'aws' => [
            'class' => \League\Flysystem\Local\LocalFilesystemAdapter::class,
            'driver' => \Spiral\StorageEngine\Enum\AdapterName::LOCAL,
            'options' => [
                'rootDir' => '/tmp/',
                'host' => 'http://localhost/files/',
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
            'class' => \League\Flysystem\Local\LocalFilesystemAdapter::class,
            'driver' => \Spiral\StorageEngine\Enum\AdapterName::LOCAL,
            'options' => [
                'rootDir' => '/tmp/',
                'host' => 'http://localhost/files/',
                'visibility' => [
                    'file' => [
                        'public' => 0640,
                        'private' => 0604,
                    ],
                    'dir' => [
                        'public' => 0740,
                        'private' => 7604,
                    ],
                ],
                'write-flag' => LOCK_EX,
                'link-handling' => \League\Flysystem\Local\LocalFilesystemAdapter::DISALLOW_LINKS,
            ]
        ],
    ],
];
```
