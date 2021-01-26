<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Validation;

use Spiral\StorageEngine\Exception\ValidationException;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;
use Spiral\StorageEngine\Validation\FilePathValidator;

class FilePathValidatorTest extends AbstractUnitTest
{
    /**
     * @dataProvider getFilePathValidateList
     *
     * @param string $filePath
     * @param bool $expectedResult
     *
     * @throws \Spiral\StorageEngine\Exception\ValidationException
     */
    public function testValidateFilePath(string $filePath, bool $expectedResult): void
    {
        if (!$expectedResult) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage('File name is not suitable by format');
        }

        $result = FilePathValidator::validateFilePath($filePath);

        if ($expectedResult === true) {
            $this->assertTrue($result);
        }
    }

    /**
     * @dataProvider getServerFilePathValidateList
     *
     * @param string $filePath
     * @param bool $expectedResult
     *
     * @throws \Spiral\StorageEngine\Exception\ValidationException
     */
    public function testValidateServerFilePath(string $filePath, bool $expectedResult): void
    {
        if (!$expectedResult) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage('Server file path is not suitable by format');
        }

        $result = FilePathValidator::validateServerFilePath($filePath);

        if ($expectedResult === true) {
            $this->assertTrue($result);
        }
    }

    public function getFilePathValidateList(): array
    {
        return [
            ['file.txt', true],
            ['someDir/file.txt', true],
            ['/tmp/file.txt', true],
            ['/tmp/file 1+2*3.txt', true],
            ['/tmp/file_-(1+2*3).txt', true],
            ['file,2:=.txt', false],
        ];
    }

    public function getServerFilePathValidateList(): array
    {
        return [
            ['aws://file.txt', true],
            ['local://someDir/file.txt', true],
            ['local:///tmp/file 1+2*3.txt', true],
            ['local://tmp/file_-(1+2*3).txt', true],
            ['/tmp/file.txt', false],
            ['aws://file:txt', false],
        ];
    }
}
