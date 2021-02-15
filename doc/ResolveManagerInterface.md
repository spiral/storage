# Storage Engine

## Resolve manager interface

### Operations:
- buildUrlsList(array $files, bool $throwException = true): \Generator
  - returns urls list as generator for provided file uris
  - by default, throws exception if **any** url can't be built
  - you can use `iterator_to_array` if you want to use it as array
- buildUrl(string $uri, bool $throwException = true): ?string
  - returns url for provided uri
  - by default, throws exception if url can't be built
  
### Customization
In case you need some specific customization Resolve manager can be replaced with your specific class 
by implementing `\Spiral\StorageEngine\ResolveManagerInterface` and binding it by the interface.

#### Specific server resolver
In case you need to use some specific rules you can prepare your own resolver for a server. In description 
you should add parameter `resolver` with your specific class.
- the class must implement `\Spiral\StorageEngine\Resolver\AdapterResolverInterface`.
- the class can be inherited from `\Spiral\StorageEngine\Resolver\AbstractAdapterResolver`

#### Specific uri format
If you need some specific uri pattern you can do it via preparing your own class 
implemented by `\Spiral\StorageEngine\Parser\UriParserInterface`.
This class must work with specified DTO implements `\Spiral\StorageEngine\Parser\DTO\UriStructureInterface`

For example if you want to change separator between a server and filepath you can prepare your own class like this:
```php
class MyUriParser extends \Spiral\StorageEngine\Parser\UriParser
{
    protected const SERVER_PATH_SEPARATOR = ':++';

    protected const URI_PATTERN = '/^' . self::SERVER_PATTERN . ':++' . self::FILE_PATH_PATTERN . '$/';
}
```

After binding modification storage engine will work with uri pattern `{server}:++{filePath}`
