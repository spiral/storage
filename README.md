# Storage Engine
Storage engine provides required functionality to manage your files for different file servers 
based on provided configuration.  

Storage engine is based on [FlySystem](https://github.com/thephpleague/flysystem) and for correct work with file servers 
(except local one) you will need to provide required FlySystem adapter (take a look at concrete filesystem doc).

StorageEngine based on 2 basic interfaces:
1. [StorageInterface](doc/StorageInterface.md)
    * works with all file servers that handle files
    * works with file paths in a specific format (`{serverName}://{filePath}` by default)
      * to change it you can prepare your own `\Spiral\StorageEngine\Validation\FilePathValidatorInterface` class and make required binding
2. [ResolveManagerInterface](doc/ResolveManagerInterface.md)
    * build url (and urls list) for file download

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

## Usage
When you need to make some file operation you should use your StorageEngine object for it:
1. To perform different operations on your files you can use FilesystemOperator implemented object:
``` php
/** @var \Spiral\StorageEngine\StorageEngine $storageEngine **/
$uri = $storageEngine->write('local', 'someDir/myFile.txt', 'It is my text'); // = 'local://someDir/myFile.txt'
$streamContent = $storage->readStream($uri);
$fileSize = $storage->fileSize($uri);

$copiedUri = $storageEngine->copy($uri, 'aws', 'myCopy.txt'); // = 'aws://myCopy.txt'
$mimeType = $storageEngine->mimeType($copiedUri); // = 'text/plain'
```
2. To build url to your file you can use ResolveManagerInterface object:
``` php
$resolveManager->buildUrl('local://someDir/myFile.txt'); // for example it can return smth like 'http://myhost.com/files/somedir/myfile.txt'
```
* P.S. For local server info you should define host in server description to build url. In other case it will throw exception.
# License:
MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by [Spiral Scout](https://spiralscout.com).
