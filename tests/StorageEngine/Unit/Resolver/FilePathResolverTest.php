<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;
use Spiral\StorageEngine\Resolver\FilePathResolver;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;
use Spiral\StorageEngine\Validation\FilePathValidator;

class FilePathResolverTest extends AbstractUnitTest
{
    /**
     * @dataProvider getServerFilePathsList
     *
     * @param string $filePath
     * @param ServerFilePathStructure|null $filePathStructure
     */
    public function testParseFilePath(string $filePath, ?ServerFilePathStructure $filePathStructure = null): void
    {
        $resolver = new FilePathResolver(new FilePathValidator());

        $this->assertEquals($filePathStructure, $resolver->parseServerFilePathToStructure($filePath));
    }

    /**
     * @dataProvider getFilePathListForBuild
     *
     * @param string $server
     * @param string $filePath
     * @param string $expectedFilePath
     *
     * @throws ValidationException
     */
    public function testBuildServerFilePath(string $server, string $filePath, string $expectedFilePath): void
    {
        $resolver = new FilePathResolver(new FilePathValidator());

        $this->assertEquals($expectedFilePath, $resolver->buildServerFilePath($server, $filePath));
    }

    /**
     * @throws ValidationException
     */
    public function testBuildServerFilePathForServerFilePath(): void
    {
        $filePath = 'aws://someDir/file1.txt';
        $resolver = new FilePathResolver(new FilePathValidator());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File path is not suitable by format');

        $resolver->buildServerFilePath('local', $filePath);
    }

    public function getServerFilePathsList(): array
    {
        $filePathValidator = new FilePathValidator();

        $fileTxt = 'file.txt';
        $dirFile = 'some/debug/dir/file1.csv';

        $filePathStruct1 = new ServerFilePathStructure('', $filePathValidator->getServerFilePathPattern());
        $filePathStruct1->serverName = ServerTestInterface::SERVER_NAME;
        $filePathStruct1->filePath = $fileTxt;

        $filePathStruct2 = new ServerFilePathStructure('', $filePathValidator->getServerFilePathPattern());
        $filePathStruct2->serverName = ServerTestInterface::SERVER_NAME;
        $filePathStruct2->filePath = $dirFile;

        return [
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                $filePathStruct1
            ],
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $dirFile),
                $filePathStruct2
            ],
            [
                \sprintf('%s:\\some/wrong/format/%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                null
            ],
        ];
    }

    public function getFilePathListForBuild(): array
    {
        return [
            [
                'local',
                'file1.txt',
                'local://file1.txt',
            ],
            [
                'aws',
                'dir/file1.txt',
                'aws://dir/file1.txt',
            ],
            [
                'ftp',
                'dir/specific/file1.txt',
                'ftp://dir/specific/file1.txt',
            ],
        ];
    }
}
