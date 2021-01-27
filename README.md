# Storage Engine
Storage engine provides required functionality to manage your files for different file servers 
based on provided configuration.  

Storage engine is based on [FlySystem](https://github.com/thephpleague/flysystem) and for correct work with file servers 
(except local one) you will need to provide required FlySystem adapter (take a look at concrete filesystem doc).

StorageEngine based on 2 classes:
1. StorageEngine
    * works with all file servers that handle files
    * works with file paths in specific format `{serverName}://{filePath}`
2. ResolveManager
    * build server path for filepaths storage
    * parse file path from db format to identify used server
    * build url for file download
    * can be replaced with your specific class by implementing `\Spiral\StorageEngine\Resolver\ResolveManagerInterface` and binding it

# Supported file servers
Current release provides ability to work with:
- [Local filesystem](doc/local.md)
- [Aws S3 (+async)](doc/awsS3.md)

# Configuration
You can configure file servers usage in Spiral with configuration file `storage.php` located in configuration directory.

You can receive more details about spiral configuration from [here](https://spiral.dev/docs/start-configuration).

More details about specific file servers configuration you can find [here](#supported-file-servers)

# Basic usage
## If you are use Spiral Framework
When you finish your configuration file you should add `Spiral\StorageEngine\Bootloader\StorageEngineBootloader` in your app.

When you need to make some file operation you should use your StorageEngine object for it:
1. To perform different operations on your files:
``` php
/** @var \Spiral\StorageEngine\StorageEngine $storageEngine **/
$storageEngine->getMountManager()->write('local://someDir/myFile.txt', 'It is my text');
```
2. To build filepath, for example to store it in db later you can use ResolveManagerInterface object:
``` php
$resolveManager->buildServerFilePath('local','someDir/myFile.txt'); // => local://someDir/myFile.txt
```
3. To build url to your file you can use ResolveManagerInterface object:
``` php
$resolveManager->buildUrl('local://someDir/myFile.txt');
```
# License:
MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by [Spiral Scout](https://spiralscout.com).
