<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\StorageConfig;
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

    private StorageConfig $config;

    private UriResolverInterface $uriResolver;

    /**
     * @param StorageConfig $config
     * @param UriResolverInterface $uriResolver
     *
     * @throws StorageException
     */
    public function __construct(StorageConfig $config, UriResolverInterface $uriResolver)
    {
        $this->config = $config;
        $this->uriResolver = $uriResolver;

        if (!empty($config->getServersKeys())) {
            foreach ($config->getServersKeys() as $serverKey) {
                if (!is_string($serverKey) || empty($serverKey)) {
                    throw new MountException(
                        \sprintf(
                            'Server %s can\'t be mounted - string required, %s received',
                            is_scalar($serverKey) && !empty($serverKey) ? $serverKey : '--non-displayable--',
                            empty($serverKey) ? 'empty val' : gettype($serverKey)
                        )
                    );
                }

                $serverInfo = AdapterFactory::build($this->config->buildServerInfo($serverKey));

                if (!$serverInfo instanceof FilesystemAdapter) {
                    throw new MountException(
                        \sprintf(
                            'Server %s can\'t be mounted - filesystem has wrong type - %s received',
                            $serverKey,
                            gettype($serverInfo)
                        )
                    );
                }

                $this->mountFilesystem($serverKey,new Filesystem($serverInfo));
            }
        }
    }

    protected function mountFilesystem(string $key, FilesystemOperator $filesystem): void
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
     * @return string
     *
     * @throws StorageException
     */
    public function tempFilename(string $uri = null): string
    {
        try {
            $prefix = 'tmpStorageFile_';

            if ($uri !== null) {
                /** @var FilesystemOperator $filesystem */
                [$filesystem, $path] = $this->determineFilesystemAndPath($uri);
                $content = $filesystem->read($path);
                $prefix = basename($uri) . '_';
            }

            $filePath = tempnam($this->config->getTmpDir(), $prefix);

            if (isset($content)) {
                file_put_contents($filePath, $content);
            }

            return $filePath;
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     *
     * @return bool
     *
     * @throws StorageException
     */
    public function fileExists(string $uri): bool
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->fileExists($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     *
     * @return string
     *
     * @throws StorageException
     */
    public function read(string $uri): string
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->read($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     *
     * @return resource
     *
     * @throws StorageException
     */
    public function readStream(string $uri)
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->readStream($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     *
     * @return int
     *
     * @throws StorageException
     */
    public function lastModified(string $uri): int
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->lastModified($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     *
     * @return int
     *
     * @throws StorageException
     */
    public function fileSize(string $uri): int
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->fileSize($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     *
     * @return string
     *
     * @throws StorageException
     */
    public function mimeType(string $uri): string
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->mimeType($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     *
     * @return string
     *
     * @throws StorageException
     */
    public function visibility(string $uri): string
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            return $filesystem->visibility($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
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
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
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
     * @throws StorageException
     */
    public function writeStream(string $server, string $filePath, $content, array $config = []): string
    {
        $uri = $this->uriResolver->buildUri($server, $filePath);

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            $filesystem->writeStream($path, $content, $config);

            return $uri;
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     * @param string $visibility
     *
     * @throws StorageException
     */
    public function setVisibility(string $uri, string $visibility): void
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            $filesystem->setVisibility($path, $visibility);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     *
     * @throws StorageException
     */
    public function delete(string $uri): void
    {
        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($uri);

        try {
            $filesystem->delete($path);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $sourceUri
     * @param string $destinationServer
     * @param string|null $targetFilePath
     * @param array $config
     *
     * @throws StorageException
     */
    public function move(
        string $sourceUri,
        string $destinationServer,
        ?string $targetFilePath = null,
        array $config = []
    ): void {
        /** @var FilesystemOperator $sourceFilesystem */
        [$sourceFilesystem, $sourcePath] = $this->determineFilesystemAndPath($sourceUri);

        $destinationFilesystem = $this->getFileSystem($destinationServer);

        if ($destinationFilesystem === null) {
            throw new MountException(\sprintf('Server %s was not identified', $destinationServer));
        }

        try {
            $sourceFilesystem === $destinationFilesystem
                ? $this->moveInTheSameFilesystem(
                    $sourceFilesystem,
                    $sourcePath,
                    $targetFilePath ?: $sourcePath,
                    $config
                )
                : $this->moveAcrossFilesystems($sourceUri, $destinationServer, $targetFilePath, $config);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $sourceUri
     * @param string $destinationServer
     * @param string|null $targetFilePath
     * @param array $config
     *
     * @throws StorageException
     */
    public function copy(
        string $sourceUri,
        string $destinationServer,
        ?string $targetFilePath = null,
        array $config = []
    ): void {
        /** @var FilesystemOperator $sourceFilesystem */
        [$sourceFilesystem, $sourcePath] = $this->determineFilesystemAndPath($sourceUri);

        $destinationFilesystem = $this->getFileSystem($destinationServer);

        if ($destinationFilesystem === null) {
            throw new MountException(\sprintf('Server %s was not identified', $destinationServer));
        }

        try {
            $sourceFilesystem === $destinationFilesystem ? $this->copyInSameFilesystem(
                $sourceFilesystem,
                $sourcePath,
                $targetFilePath ?: $sourcePath,
                $config
            ) : $this->copyAcrossFilesystem(
                $config['visibility'] ?? null,
                $sourceFilesystem,
                $sourcePath,
                $destinationFilesystem,
                $targetFilePath ?: $sourcePath,
                $config
            );
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
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
     * @param array $config
     *
     * @throws FilesystemException
     */
    protected function copyInSameFilesystem(
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        string $destinationPath,
        array $config = []
    ): void {
        $sourceFilesystem->copy($sourcePath, $destinationPath, $config);
    }

    /**
     * @param string|null $visibility
     * @param FilesystemOperator $sourceFilesystem
     * @param string $sourcePath
     * @param FilesystemOperator $destinationFilesystem
     * @param string $destinationPath
     * @param array $config
     *
     * @throws FilesystemException
     */
    protected function copyAcrossFilesystem(
        ?string $visibility,
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        FilesystemOperator $destinationFilesystem,
        string $destinationPath,
        array $config = []
    ): void {
        $visibility = $visibility ?? $sourceFilesystem->visibility($sourcePath);
        $stream = $sourceFilesystem->readStream($sourcePath);
        $destinationFilesystem->writeStream(
            $destinationPath,
            $stream,
            !empty($config)
                ? array_merge($config, compact('visibility'))
                : compact('visibility')
        );
    }

    /**
     * @param FilesystemOperator $sourceFilesystem
     * @param string $sourcePath
     * @param string $destinationPath
     * @param array $config
     *
     * @throws FilesystemException
     */
    protected function moveInTheSameFilesystem(
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        string $destinationPath,
        array $config = []
    ): void {
        if ($sourcePath === $destinationPath && empty($config)) {
            return;
        }

        $sourceFilesystem->move($sourcePath, $destinationPath, $config);
    }

    /**
     * @param string $sourceUri
     * @param string $destinationServer
     * @param string|null $targetFilePath
     * @param array $config
     *
     * @throws StorageException
     */
    protected function moveAcrossFilesystems(
        string $sourceUri,
        string $destinationServer,
        ?string $targetFilePath = null,
        array $config = []
    ): void {
        $this->copy($sourceUri, $destinationServer, $targetFilePath, $config);
        $this->delete($sourceUri);
    }
}
