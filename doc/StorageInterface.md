# Storage Engine

## Storage interface
### Basic operations:   
- getFileSystem(string $key): FilesystemOperator
  - allows to receive some specific filesystem operator by key for detailed operations
- getFileSystemNames(): array
  - allows getting keys list for all mounted filesystems

### Read operations:
- fileExists(string $uri): bool
  - check if file exists
- read(string $uri): string
  - read file and returns file content as string  
- readStream(string $uri)
  - read file and returns file content as stream  
- lastModified(string $uri): int
  - returns last modified date as timestamp
- fileSize(string $uri): int
  - returns file size in bytes
- mimeType(string $uri): string
  - returns mime type
- visibility(string $uri): string
  - returns visibility (public/private)

### Write operations:
- tempFilename(?string $uri = null): string
  - allocate a file for temp operations like specific process
  - temp directory will be used for file
    - by default, it is system temp directory
    - can be defined as `tmp-dir` param in `storage.php`
  - without arguments will be created empty file
  - with provided uri file will be read and its content will be used for temp file
  - returns path to new temp file
- write(string $server, string $filePath, string $content, array $config = []): string
  - write provided content to file on server
  - returns uri to new file
- writeStream(string $server, string $filePath, $content, array $config = []): string
  - write provided stream resource to file on server
  - returns uri to new file
- setVisibility(string $uri, string $visibility): void
  - set visibility for the file (public/private)
  - no return
- copy(string $sourceUri, string $destinationServer, ?string $targetFilePath = null, array $config = [])
  - copy file from uri to new destination at same server or other
  - without targetFilePath filepath on destination server will be equals to source filepath
  - returns result file uri
- move(string $sourceUri, string $destinationServer, ?string $targetFilePath = null, array $config = [])
  - move file from uri to new destination at same server or other
  - without targetFilePath filepath on destination server will be equals to source filepath
- returns result file uri
- delete(string $uri): void
  - delete file by uri
