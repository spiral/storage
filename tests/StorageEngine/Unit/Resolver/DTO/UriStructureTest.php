<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver\DTO;

use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Resolver\DTO\UriStructure;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class UriStructureTest extends AbstractUnitTest
{
    /**
     * @dataProvider getUriListForConstructor
     *
     * @param string $uri
     * @param string $expectedServer
     * @param string $expectedPath
     *
     * @throws ValidationException
     */
    public function testConstructor(string $uri, string $expectedServer, string $expectedPath): void
    {
        $structure = new UriStructure($uri, $this->getFilePathValidator()->getUriPattern());

        $this->assertEquals($structure->serverName, $expectedServer);
        $this->assertEquals($structure->filePath, $expectedPath);
    }

    /**
     * @dataProvider getFailedUriListForConstructor
     *
     * @param string $uri
     * @param string $expectedExceptionMsg
     *
     * @throws ValidationException
     */
    public function testConstructorThrowsException(string $uri, string $expectedExceptionMsg): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedExceptionMsg);

        new UriStructure($uri, $this->getFilePathValidator()->getUriPattern());
    }

    public function getUriListForConstructor(): array
    {
        return [
            ['local://file.txt', 'local', 'file.txt'],
            ['aws://dir/file.txt', 'aws', 'dir/file.txt'],
        ];
    }

    public function getFailedUriListForConstructor(): array
    {
        return [
            ['://file.txt', 'No server was detected in uri ://file.txt'],
            ['aws://', 'No path was detected in uri'],
            ['aws+-/someFile.txt', 'No uri structure was detected in uri aws+-/someFile.txt']
        ];
    }
}
