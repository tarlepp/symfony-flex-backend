<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Utils/Tests/PHPUnitUtilTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Utils\Tests;

use App\Entity\User;
use App\Utils\Tests\PhpUnitUtil;
use DateTime;
use DateTimeImmutable;
use Generator;
use LogicException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class PHPUnitUtilTest
 *
 * @package App\Tests\Unit\Utils\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class PHPUnitUtilTest extends KernelTestCase
{
    public function testThatGetTypeThrowsAnExceptionWithNotKnowType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Currently type \'666\' is not supported within type normalizer');

        PhpUnitUtil::getType('666');
    }

    /**
     * @dataProvider dataProviderTestThatGetTypeReturnExpected
     *
     * @param string $expected
     * @param string $input
     *
     * @testdox Test that `getType` method returns `$expected` with `$input` input.
     */
    public function testThatGetTypeReturnExpected(string $expected, string $input): void
    {
        static::assertSame($expected, PhpUnitUtil::getType($input));
    }

    /**
     * @throws Throwable
     */
    public function testThatGetValidValueForTypeThrowsAnExceptionWithNotKnowType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot create valid value for type \'666\'.');

        PhpUnitUtil::getValidValueForType('666');
    }

    /**
     * @dataProvider dataProviderTestThatGetValidValueReturnsExpectedValue
     *
     * @param mixed  $expected
     * @param string $input
     * @param bool   $strict
     *
     * @throws Throwable
     *
     * @testdox Test that `getValidValueForType` method returns `$expected` with `$input` and strict mode `$strict`.
     */
    public function testThatGetValidValueReturnsExpectedValue($expected, string $input, bool $strict): void
    {
        $value = PhpUnitUtil::getValidValueForType(PhpUnitUtil::getType($input));

        $strict ? static::assertSame($expected, $value) : static::assertInstanceOf($expected, $value);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetValidValueForTypeWorksWithCustomType(): void
    {
        static::assertInstanceOf(User::class, PhpUnitUtil::getValidValueForType(User::class));
    }

    /**
     * @throws Throwable
     */
    public function testThatGetInvalidValueForTypeThrowsAnExceptionWithNotKnowType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot create invalid value for type \'666\'.');

        PhpUnitUtil::getInvalidValueForType('666');
    }

    /**
     * @dataProvider dataProviderTestThatGetInvalidValueForTypeReturnsExpectedValue
     *
     * @param mixed $expected
     * @param string $input
     *
     * @throws Throwable
     *
     * @testdox Test that `getInvalidValueForType` method returns `$expected` with `$input` input.
     */
    public function testThatGetInvalidValueForTypeReturnsExpectedValue($expected, string $input): void
    {
        static::assertInstanceOf($expected, PhpUnitUtil::getInvalidValueForType($input));
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetInvalidValueForTypeReturnsExpectedValue(): Generator
    {
        yield [DateTime::class, stdClass::class];
        yield [DateTime::class, DateTimeImmutable::class];
        yield [stdClass::class, User::class];
        yield [stdClass::class, 'integer'];
        yield [stdClass::class, DateTime::class];
        yield [stdClass::class, 'string'];
        yield [stdClass::class, 'array'];
        yield [stdClass::class, 'boolean'];
        yield [stdClass::class, 'bool'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetTypeReturnExpected(): Generator
    {
        yield ['int', 'integer'];
        yield ['int', 'bigint'];
        yield [DateTime::class, 'time'];
        yield [DateTime::class, 'date'];
        yield [DateTime::class, 'datetime'];
        yield [DateTimeImmutable::class, 'time_immutable'];
        yield [DateTimeImmutable::class, 'date_immutable'];
        yield [DateTimeImmutable::class, 'datetime_immutable'];
        yield ['string', 'string'];
        yield ['string', 'text'];
        yield ['array', 'array'];
        yield ['bool', 'boolean'];
        yield ['bool', 'bool'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetValidValueReturnsExpectedValue(): Generator
    {
        yield [666, 'int', true];
        yield [666, 'integer', true];
        yield [666, 'bigint', true];
        yield [DateTime::class, 'time', false];
        yield [DateTime::class, 'date', false];
        yield [DateTime::class, 'datetime', false];
        yield [DateTimeImmutable::class, 'time_immutable', false];
        yield [DateTimeImmutable::class, 'date_immutable', false];
        yield [DateTimeImmutable::class, 'datetime_immutable', false];
        yield ['Some text here', 'string', true];
        yield [['some', 'array', 'here'], 'array', true];
        yield [true, 'boolean', true];
        yield [true, 'bool', true];
    }
}
