<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Resolver\DTO;

use Spiral\StorageEngine\Resolver\DTO\ServerFilePathStructure;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class ServerFilePathStructureTest extends AbstractUnitTest
{
    /**
     * @dataProvider getServerFilePathsListForCheck
     *
     * @param string $filePath
     * @param bool $expectedResult
     */
    public function testIsIdentified(string $filePath, bool $expectedResult): void
    {
        $structure = new ServerFilePathStructure($filePath);

        $this->assertEquals($structure->isIdentified(), $expectedResult);
    }

    public function getServerFilePathsListForCheck(): array
    {
        return [
            ['file.txt', false],
            ['local://file.txt', true],
            ['local://dir/file.txt', true],
        ];
    }
}
