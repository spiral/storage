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
     * @throws ValidationException
     */
    public function testValidateFilePath(string $filePath, bool $expectedResult): void
    {
        $filePathValidator = $this->getFilePathValidator();

        if (!$expectedResult) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage('File path is not suitable by format');
        }

        $filePathValidator->validateFilePath($filePath);

        if ($expectedResult === true) {
            $this->expectNotToPerformAssertions();
        }
    }

    /**
     * @dataProvider getServerFilePathValidateList
     *
     * @param string $filePath
     * @param bool $expectedResult
     *
     * @throws ValidationException
     */
    public function testValidateServerFilePath(string $filePath, bool $expectedResult): void
    {
        $filePathValidator = $this->getFilePathValidator();

        if (!$expectedResult) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage('Server file path is not suitable by format');
        }

        $filePathValidator->validateServerFilePath($filePath);

        if ($expectedResult === true) {
            $this->expectNotToPerformAssertions();
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
