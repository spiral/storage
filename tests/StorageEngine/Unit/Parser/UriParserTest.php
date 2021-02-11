<?php

namespace Spiral\StorageEngine\Tests\Unit\Parser;

use Spiral\StorageEngine\Exception\UriException;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class UriParserTest extends AbstractUnitTest
{
    /**
     * @dataProvider getUriList
     *
     * @param string $server
     * @param string $path
     * @param string $uri
     */
    public function testPrepareUri(string $server, string $path, string $uri): void
    {
        $uriStructure = $this->getUriParser()->prepareUri($server, $path);

        $this->assertEquals($server, $uriStructure->getServer());
        $this->assertEquals($path, $uriStructure->getPath());
        $this->assertEquals($uri, (string)$uriStructure);
    }

    /**
     * @dataProvider getUriList
     *
     * @param string $server
     * @param string $path
     * @param string $uri
     *
     * @throws UriException
     */
    public function testParseUri(string $server, string $path, string $uri): void
    {
        $uriStructure = $this->getUriParser()->parseUri($uri);

        $this->assertEquals($server, $uriStructure->getServer());
        $this->assertEquals($path, $uriStructure->getPath());
        $this->assertEquals($uri, (string)$uriStructure);
    }

    /**
     * @dataProvider getBadUriList
     *
     * @param string $uri
     * @param string $expectedMsg
     *
     * @throws UriException
     */
    public function testParseUriThrowsException(string $uri, string $expectedMsg): void
    {
        $this->expectException(UriException::class);
        $this->expectExceptionMessage($expectedMsg);

        $this->getUriParser()->parseUri($uri);
    }

    /**
     * @dataProvider getUriListWithSeparators
     *
     * @param string $server
     * @param string $path
     * @param string $uri
     * @param string|null $separator
     *
     * @throws \ReflectionException
     */
    public function testBuildUriStructure(string $server, string $path, string $uri, ?string $separator = null): void
    {
        $uriStructure = $this->callNotPublicMethod(
            $this->getUriParser(),
            'buildUriStructure',
            [$server, $path, $separator]
        );

        $this->assertEquals($server, $uriStructure->getServer());
        $this->assertEquals($path, $uriStructure->getPath());
        $this->assertEquals($uri, (string)$uriStructure);
    }

    public function getUriList(): array
    {
        return [
            [
                'local',
                'file.txt',
                'local://file.txt',
            ],
            [
                'aws',
                'some/specific/dir/dirFile.txt',
                'aws://some/specific/dir/dirFile.txt',
            ],
        ];
    }

    public function getUriListWithSeparators(): array
    {
        return [
            [
                'local',
                'file.txt',
                'local://file.txt',
                null,
            ],
            [
                'local',
                'dir/file.txt',
                'local://dir/file.txt',
                '://',
            ],
            [
                'aws',
                'some/specific/dir/dirFile.txt',
                'aws+-+some/specific/dir/dirFile.txt',
                '+-+',
            ],
        ];
    }

    public function getBadUriList(): array
    {
        $noServerUri = '://file.txt';
        $noPathUri = 'aws://';

        return [
            [
                $noServerUri,
                'No server was detected in uri ' . $noServerUri,
            ],
            [
                $noPathUri,
                'No path was detected in uri ' . $noPathUri,
            ],
        ];
    }
}
