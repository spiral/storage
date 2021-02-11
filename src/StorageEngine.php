<?php

declare(strict_types=1);

namespace Spiral\StorageEngine;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Spiral\Core\Container\SingletonInterface;
use Spiral\StorageEngine\Builder\AdapterFactory;
use Spiral\StorageEngine\Config\StorageConfig;
use Spiral\StorageEngine\Exception\FileOperationException;
use Spiral\StorageEngine\Exception\MountException;
use Spiral\StorageEngine\Exception\StorageException;
use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Parser\UriParserInterface;

class StorageEngine implements StorageInterface, SingletonInterface
{
    protected StorageConfig $config;

    protected UriParserInterface $uriParser;

    /**
     * @var array<string, FilesystemOperator>
     */
    protected array $fileSystems = [];

    /**
     * @param StorageConfig $config
     * @param UriParserInterface $uriParser
     *
     * @throws StorageException
     */
    public function __construct(StorageConfig $config, UriParserInterface $uriParser)
    {
        $this->config = $config;
        $this->uriParser = $uriParser;

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

                $this->mountFilesystem(
                    $serverKey,
                    new Filesystem(
                        AdapterFactory::build($this->config->buildServerInfo($serverKey))
                    )
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getFileSystem(string $key): FilesystemOperator
    {
        if (!$this->isFileSystemExists($key)) {
            throw new MountException(
                \sprintf('Server %s was not identified', $key)
            );
        }

        return $this->fileSystems[$key];
    }

    public function getFileSystemsNames(): array
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
                $content = $filesystem->readStream($path);
                $prefix = basename($uri) . '_';
            }

            $filePath = tempnam($this->config->getTmpDir(), $prefix);

            if (isset($content)) {
                file_put_contents($filePath, $content);
            }

            return $filePath;
        } catch (\Throwable $e) {
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
        $uri = (string)$this->uriParser->prepareUri($server, $filePath);

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
        $uri = (string)$this->uriParser->prepareUri($server, $filePath);

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
     * @return string
     *
     * @throws StorageException
     */
    public function move(
        string $sourceUri,
        string $destinationServer,
        ?string $targetFilePath = null,
        array $config = []
    ): string {
        /** @var FilesystemOperator $sourceFilesystem */
        [$sourceFilesystem, $sourcePath] = $this->determineFilesystemAndPath($sourceUri);

        $destinationFilesystem = $this->getFileSystem($destinationServer);

        try {
            $targetFilePath = $targetFilePath ?: $sourcePath;

            $sourceFilesystem === $destinationFilesystem
                ? $this->moveInTheSameFilesystem($sourceFilesystem, $sourcePath, $targetFilePath, $config)
                : $this->moveAcrossFilesystems($sourceUri, $destinationServer, $targetFilePath, $config);

            return (string)$this->uriParser->prepareUri($destinationServer, $targetFilePath);
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
     * @return string
     *
     * @throws StorageException
     */
    public function copy(
        string $sourceUri,
        string $destinationServer,
        ?string $targetFilePath = null,
        array $config = []
    ): string {
        /** @var FilesystemOperator $sourceFilesystem */
        [$sourceFilesystem, $sourcePath] = $this->determineFilesystemAndPath($sourceUri);

        $destinationFilesystem = $this->getFileSystem($destinationServer);

        try {
            $targetFilePath = $targetFilePath ?: $sourcePath;

            $sourceFilesystem === $destinationFilesystem
                ? $this->copyInSameFilesystem($sourceFilesystem, $sourcePath, $targetFilePath, $config)
                : $this->copyAcrossFilesystem(
                    $config['visibility'] ?? null,
                    $sourceFilesystem,
                    $sourcePath,
                    $destinationFilesystem,
                    $targetFilePath,
                    $config
                );

            return (string)$this->uriParser->prepareUri($destinationServer, $targetFilePath);
        } catch (FilesystemException $e) {
            throw new FileOperationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function mountFilesystem(string $key, FilesystemOperator $filesystem): void
    {
        if ($this->isFileSystemExists($key)) {
            return;
        }

        $this->fileSystems[$key] = $filesystem;
    }

    protected function isFileSystemExists(string $key): bool
    {
        return array_key_exists($key, $this->fileSystems);
    }

    /**
     * @param string $uri
     *
     * @return array{0:FilesystemOperator, 1:string}
     *
     * @throws MountException
     * @throws UriException
     */
    protected function determineFilesystemAndPath(string $uri): array
    {
        $uriStructure = $this->uriParser->parseUri($uri);

        return [$this->getFileSystem($uriStructure->server), $uriStructure->path];
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
