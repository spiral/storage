<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver;

use Spiral\StorageEngine\Exception\ResolveException;
use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Resolver\DTO\UriStructure;
use Spiral\StorageEngine\Tests\Interfaces\ServerTestInterface;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class UriResolverTest extends AbstractUnitTest
{
    /**
     * @dataProvider getUriList
     *
     * @param string $uri
     * @param UriStructure|null $uriStructure
     *
     * @throws ResolveException
     */
    public function testParseUriToStructure(string $uri, ?UriStructure $uriStructure = null): void
    {
        $resolver = $this->getUriResolver();

        $this->assertEquals($uriStructure, $resolver->parseUriToStructure($uri));
    }

    public function testParseUriToStructureFailed(): void
    {
        $uri = 'serverX:\\some/wrong/format/file.txt';

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage(\sprintf('File %s can\'t be identified', $uri));

        $resolver = $this->getUriResolver();
        $resolver->parseUriToStructure($uri);
    }

    /**
     * @dataProvider getFilePathListForBuild
     *
     * @param string $server
     * @param string $filePath
     * @param string $expectedUri
     *
     * @throws ValidationException
     */
    public function testBuildUri(string $server, string $filePath, string $expectedUri): void
    {
        $resolver = $this->getUriResolver();

        $this->assertEquals($expectedUri, $resolver->buildUri($server, $filePath));
    }

    /**
     * @throws ValidationException
     */
    public function testBuildUriFoCorrectUri(): void
    {
        $uri = 'aws://someDir/file1.txt';
        $resolver = $this->getUriResolver();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File path is not suitable by format');

        $resolver->buildUri('local', $uri);
    }

    public function getUriList(): array
    {
        $filePathValidator = $this->getFilePathValidator();

        $fileTxt = 'file.txt';
        $dirFile = 'some/debug/dir/file1.csv';

        $uriStruct1 = new UriStructure('', $filePathValidator->getUriPattern());
        $uriStruct1->serverName = ServerTestInterface::SERVER_NAME;
        $uriStruct1->filePath = $fileTxt;

        $uriStruct2 = new UriStructure('', $filePathValidator->getUriPattern());
        $uriStruct2->serverName = ServerTestInterface::SERVER_NAME;
        $uriStruct2->filePath = $dirFile;

        return [
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $fileTxt),
                $uriStruct1
            ],
            [
                \sprintf('%s://%s', ServerTestInterface::SERVER_NAME, $dirFile),
                $uriStruct2
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
