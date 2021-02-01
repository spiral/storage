<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver\DTO;

use Spiral\StorageEngine\Resolver\DTO\UriStructure;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class UriStructureTest extends AbstractUnitTest
{
    /**
     * @dataProvider getUriListForCheck
     *
     * @param string $uri
     * @param bool $expectedResult
     */
    public function testIsIdentified(string $uri, bool $expectedResult): void
    {
        $filePathValidator = $this->getFilePathValidator();

        $structure = new UriStructure($uri, $filePathValidator->getUriPattern());

        $this->assertEquals($structure->isIdentified(), $expectedResult);
    }

    public function getUriListForCheck(): array
    {
        return [
            ['file.txt', false],
            ['local://file.txt', true],
            ['local://dir/file.txt', true],
        ];
    }
}
