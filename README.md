# Storage Engine
Storage engine provides required functionality to manage your files for different file servers 
based on provided configuration.  

Storage engine based on [FlySystem](https://github.com/thephpleague/flysystem) and for correct work with file servers 
except local you will need to provide required FlySystem adapter (take a look at concrete filesystem doc).

Base class `\Spiral\StorageEngine\StorageEngine` provides 2 manager classes:
1. MountManager
    * work with all defined file servers for work with files
    * works with file paths in specific format `{serverName}://{filePath}`
2. ResolveManager
    * build server path in format required by MountManager
    * parse file path from MountManager format to identify used server
    * build url for file download
    * can be replaced with your specific class by implementing `\Spiral\StorageEngine\Resolver\ResolveManagerInterface` by binding

# Supported file servers
Current release provides ability to work with:
- [Local filesystem](doc/local.md)
- [Aws S3 (+async)](doc/awsS3.md)

# Configuration
To provide file servers description you should provide spiral configuration file `storage.php` in configuration directory.

More details about spiral configuration you can receive from [here](https://spiral.dev/docs/start-configuration).

More details about specific file servers configuration you can find [here](#supported-file-servers)

# Basic usage
When you will finish your configuration file you should add `Spiral\StorageEngine\Bootloader\StorageEngineBootloader` in your app.

When you need to make some operation you should use your StorageEngine object when you need it to:
1. For different operations with files:
``` php
/** @var \Spiral\StorageEngine\StorageEngine $storageEngine **/
$storageEngine->getMountManager()->write('local://someDir/myFile.txt', 'It is my text');
```
2. For building filepath to store it in db:
``` php
$storageEngine->getResolveManager()->buildServerFilePath('local','someDir/myFile.txt');
```
3. For building url to your file:
``` php
$storageEngine->getResolveManager()->buildUrl('local://someDir/myFile.txt');
```
# License:
MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by [Spiral Scout](https://spiralscout.com).
