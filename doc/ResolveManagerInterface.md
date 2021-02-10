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
by implementing `\Spiral\StorageEngine\ResolveManagerInterface` and binding it by interface.

### Specific server resolver
In case you need to use some specific rules you can prepare your own resolver for server. In description 
you should add parameter `resolver` with your specific class.
- the class must implements `\Spiral\StorageEngine\Resolver\AdapterResolver\AdapterResolverInterface`.
- the class can be inherited from `\Spiral\StorageEngine\Resolver\AdapterResolver\AbstractAdapterResolver`

### Specific uri format
If you need some specific uri pattern you can do it via preparing your own class 
implemented by `\Spiral\StorageEngine\Validation\FilePathValidatorInterface`.
This class should provide uri pattern with named parts `server` and `path`
