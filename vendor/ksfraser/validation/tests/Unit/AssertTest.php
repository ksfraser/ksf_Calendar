<?php

namespace Ksfraser\Validation\Tests\Unit;

use Ksfraser\Validation\Assert;
use Ksfraser\Validation\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class AssertTest extends TestCase
{
    public function testNotEmptyStringAcceptsNonEmpty(): void
    {
        Assert::notEmptyString('abc', 'field');
        $this->assertTrue(true);
    }

    public function testNotEmptyStringRejectsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        Assert::notEmptyString('   ', 'field');
    }

    public function testStringMaxLenRejectsTooLong(): void
    {
        $this->expectException(ValidationException::class);
        Assert::stringMaxLen('abcd', 3, 'field');
    }

    public function testIntBetweenWorksWithNumericString(): void
    {
        Assert::intBetween('10', 0, 20, 'field');
        $this->assertTrue(true);
    }
}
