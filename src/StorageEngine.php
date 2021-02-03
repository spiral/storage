<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Exception\FileOperationException;
use Spiral\StorageEngine\Exception\MountException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Resolver\UriResolverInterface;

class StorageEngine implements StorageInterface, SingletonInterface
{
    /**
     * @var array<string, FilesystemOperator>
     */
    protected array $fileSystems = [];

    private UriResolverInterface $uriResolver;

    /**
     * @param UriResolverInterface $uriResolver
     * @param array<string,FilesystemOperator> $filesystems
     *
     * @throws StorageException
     */
    public function __construct(UriResolverInterface $uriResolver, array $filesystems = [])
    {
        $this->uriResolver = $uriResolver;

        if (!empty($filesystems)) {
            $this->mountFileSystems($filesystems);
        }
    }

    /**
     * @param array $filesystems
     *
     * @throws StorageException
     */
    public function mountFileSystems(array $filesystems): void
    {
        foreach ($filesystems as $key => $fileSystem) {
            if (!is_string($key) || empty($key)) {
                throw new MountException(
                    \sprintf(
                        'Server %s can\'t be mounted - string required, %s received',
                        is_scalar($key) && !empty($key) ? $key : '--non-displayable--',
                        empty($key) ? 'empty val' : gettype($key)
                    )
                );
            }

            if (!$fileSystem instanceof FilesystemOperator) {
                throw new MountException(
                    \sprintf(
                        'Server %s can\'t be mounted - filesystem has wrong type - %s received',
                        $key,
                        gettype($fileSystem)
                    )
                );
            }

            $this->mountFilesystem($key, $fileSystem);
        }
    }

    public function mountFilesystem(string $key, FilesystemOperator $filesystem): void
    {
        if ($this->isFileSystemExists($key)) {
            return;
        }

        $this->fileSystems[$key] = $filesystem;
    }

    public function isFileSystemExists(string $key): bool
    {
        return array_key_exists($key, $this->fileSystems);
    }

    public function getFileSystem(string $key): ?FilesystemOperator
    {
        return $this->isFileSystemExists($key) ? $this->fileSystems[$key] : null;
    }

    public function extractMountedFileSystemsKeys(): array
    {
        return array_keys($this->fileSystems);
    }

    /**
     * @param string|null $uri
     *
     * @return string
     */
    public function tempFilename(string $uri = null): string
    {
        // TODO: Implement tempFilename() method.
    }

    /**
     * @param string $uri
     *
     * @return bool
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function fileExists(string $uri): bool
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->fileExists($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to check file existence for `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $uri
     *
     * @return string
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function read(string $uri): string
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->read($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to read file from `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $uri
     *
     * @return resource
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function readStream(string $uri)
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->readStream($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to read file from `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $uri
     *
     * @return int
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function lastModified(string $uri): int
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->lastModified($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to retrieve the last modified for file at `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $uri
     *
     * @return int
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function fileSize(string $uri): int
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->fileSize($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to retrieve the file size for file at `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $uri
     *
     * @return string
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function mimeType(string $uri): string
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->mimeType($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to retrieve the mime type for file at `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $uri
     *
     * @return string
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function visibility(string $uri): string
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->visibility($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to retrieve the visibility for file at `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $server
     * @param string $filePath
     * @param string $content
     * @param array $config
     *
     * @return string
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function write(string $server, string $filePath, string $content, array $config = []): string
    {
        $uri = $this->uriResolver->buildUri($server, $filePath);

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            $filesystem->write($path, $content, $config);

            return $uri;
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to write file at `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $server
     * @param string $filePath
     * @param $content
     * @param array $config
     *
     * @return string
     *
     * @throws FilesystemException
     * @throws StorageException
     */
    public function writeStream(string $server, string $filePath, $content, array $config = []): string
    {
        $uri = $this->uriResolver->buildUri($server, $filePath);

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        $filesystem->writeStream($path, $content, $config);

        return $uri;
    }

    /**
     * @param string $uri
     * @param string $visibility
     *
     * @throws FilesystemException
     * @throws StorageException
     */
    public function setVisibility(string $uri, string $visibility): void
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $uri] = $this->determineFilesystemAndPath($uri);
        $filesystem->setVisibility($uri, $visibility);
    }

    /**
     * @param string $uri
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function delete(string $uri): void
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            $filesystem->delete($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to delete file `%s`: %s', $uri, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $sourceUri
     * @param string $destinationServer
     * @param string|null $targetFilePath
     * @param array $config
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function move(string $sourceUri, string $destinationServer, ?string $targetFilePath = null, array $config = []): void
    {
        /** @var FilesystemOperator $sourceFilesystem */
        /* @var FilesystemOperator $destinationFilesystem */
        [$sourceFilesystem, $sourcePath] = $this->determineFilesystemAndPath($sourceUri);

        $destinationUri = $this->uriResolver->buildUri($destinationServer, $targetFilePath ?: $sourcePath);

        $sourceFilesystem === $destinationFilesystem
            ? $this->moveInTheSameFilesystem(
                $sourceFilesystem,
                $sourcePath,
                $targetFilePath ?: $sourcePath
            )
            : $this->moveAcrossFilesystems($sourceUri, $destinationUri);
    }

    /**
     * @param string $sourceUri
     * @param string $destinationServer
     * @param string|null $targetFilePath
     * @param array $config
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    public function copy(string $sourceUri, string $destinationServer, ?string $targetFilePath = null, array $config = []): void
    {
        /** @var FilesystemOperator $sourceFilesystem */
        /* @var FilesystemOperator $destinationFilesystem */
        [$sourceFilesystem, $sourcePath] = $this->determineFilesystemAndPath($sourceUri);

        $sourceFilesystem === $destinationFilesystem ? $this->copyInSameFilesystem(
            $sourceFilesystem,
            $sourcePath,
            $targetFilePath ?: $sourcePath
        ) : $this->copyAcrossFilesystem(
            $config['visibility'] ?? null,
            $sourceFilesystem,
            $sourcePath,
            $destinationFilesystem,
            $targetFilePath ?: $sourcePath
        );
    }

    /**
     * @param string $uri
     *
     * @return array{0:FilesystemOperator, 1:string}
     *
     * @throws StorageException
     */
    protected function determineFilesystemAndPath(string $uri): array
    {
        $uriStructure = $this->uriResolver->parseUriToStructure($uri);

        if (!$this->isFileSystemExists($uriStructure->serverName)) {
            throw new StorageException(
                \sprintf('Server %s was not identified', $uriStructure->serverName)
            );
        }

        return [$this->fileSystems[$uriStructure->serverName], $uriStructure->filePath];
    }

    /**
     * @param FilesystemOperator $sourceFilesystem
     * @param string $sourcePath
     * @param string $destinationPath
     *
     * @throws FileOperationException
     */
    protected function copyInSameFilesystem(
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        string $destinationPath
    ): void {
        try {
            $sourceFilesystem->copy($sourcePath, $destinationPath);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to copy file from `%s` to `%s`: %s', $sourcePath, $destinationPath, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string|null $visibility
     * @param FilesystemOperator $sourceFilesystem
     * @param string $sourcePath
     * @param FilesystemOperator $destinationFilesystem
     * @param string $destinationPath
     *
     * @throws FileOperationException
     */
    protected function copyAcrossFilesystem(
        ?string $visibility,
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        FilesystemOperator $destinationFilesystem,
        string $destinationPath
    ): void {
        try {
            $visibility = $visibility ?? $sourceFilesystem->visibility($sourcePath);
            $stream = $sourceFilesystem->readStream($sourcePath);
            $destinationFilesystem->writeStream($destinationPath, $stream, compact('visibility'));
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to copy file from `%s` to `%s`: %s', $sourcePath, $destinationPath, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param FilesystemOperator $sourceFilesystem
     * @param string $sourcePath
     * @param string $destinationPath
     *
     * @throws FileOperationException
     */
    protected function moveInTheSameFilesystem(
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        string $destinationPath
    ): void {
        try {
            $sourceFilesystem->move($sourcePath, $destinationPath);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to move file from `%s` to `%s`: %s', $sourcePath, $destinationPath, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @throws FileOperationException
     * @throws StorageException
     */
    protected function moveAcrossFilesystems(string $source, string $destination): void
    {
        try {
            $this->copy($source, $destination);
            $this->delete($source);
        } catch (FilesystemException $e) {
            throw new FileOperationException(
                \sprintf('Unable to move file from `%s` to `%s`: %s', $source, $destination, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}
