<?php

declare(strict_types=1);

namespace Spiral\StorageEngine\Tests\Unit\Config\DTO\Traits;

use Spiral\StorageEngine\Config\DTO\Traits\OptionsTrait;
use Spiral\StorageEngine\Tests\Unit\AbstractUnitTest;

class OptionsTraitTest extends AbstractUnitTest
{
    /**
     * @throws \ReflectionException
     */
    public function testGetOption(): void
    {
        $option1 = 'option1';
        $option1Value = 'val1';
        $option2 = 'option2';
        $option2Value = 'val2';

        /** @var OptionsTrait $trait */
        $trait = $this->getMockForTrait(OptionsTrait::class);

        $refClass = new \ReflectionClass(get_class($trait));

        $protectedProperty = $refClass->getProperty('options');
        $protectedProperty->setAccessible(true);

        $protectedProperty->setValue($trait, [$option1 => $option1Value, $option2 => $option2Value]);

        $this->assertEquals($option1Value, $trait->getOption($option1));
        $this->assertEquals($option2Value, $trait->getOption($option2));

        $this->assertNull($trait->getOption('missedOption'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testHasOption(): void
    {
        $option1 = 'option1';
        $option1Value = 'val1';
        $option2 = 'option2';
        $option2Value = 'val2';

        /** @var OptionsTrait $trait */
        $trait = $this->getMockForTrait(OptionsTrait::class);

        $refClass = new \ReflectionClass(get_class($trait));

        $protectedProperty = $refClass->getProperty('options');
        $protectedProperty->setAccessible(true);

        $protectedProperty->setValue($trait, [$option1 => $option1Value, $option2 => $option2Value]);

        $this->assertTrue($trait->hasOption($option1));
        $this->assertTrue($trait->hasOption($option2));

        $this->assertFalse($trait->hasOption('missedOption'));
    }
}
