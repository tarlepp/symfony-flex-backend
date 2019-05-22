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
use Generator;
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
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Currently type '666' is not supported within type normalizer
     */
    public function testThatGetTypeThrowsAnExceptionWithNotKnowType(): void
    {
        PhpUnitUtil::getType('666');
    }

    /**
     * @dataProvider dataProviderTestThatGetTypeReturnExpected
     *
     * @param string $expected
     * @param string $input
     */
    public function testThatGetTypeReturnExpected(string $expected, string $input): void
    {
        static::assertSame($expected, PhpUnitUtil::getType($input));
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot create valid value for type '666'.
     *
     * @throws Throwable
     */
    public function testThatGetValidValueForTypeThrowsAnExceptionWithNotKnowType(): void
    {
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

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot create invalid value for type '666'.
     *
     * @throws Throwable
     */
    public function testThatGetInvalidValueForTypeThrowsAnExceptionWithNotKnowType(): void
    {
        PhpUnitUtil::getInvalidValueForType('666');
    }

    /**
     * @dataProvider dataProviderTestThatGetInvalidValueForTypeReturnsExpectedValue
     *
     * @param mixed $expected
     * @param string $input
     *
     * @throws Throwable
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
        yield ['integer', 'integer'];
        yield ['integer', 'bigint'];
        yield [DateTime::class, 'time'];
        yield [DateTime::class, 'date'];
        yield [DateTime::class, 'datetime'];
        yield ['string', 'string'];
        yield ['string', 'text'];
        yield ['array', 'array'];
        yield ['boolean', 'boolean'];
        yield ['boolean', 'bool'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetValidValueReturnsExpectedValue(): Generator
    {
        yield [666, 'integer', true];
        yield [666, 'bigint', true];
        yield [DateTime::class, 'time', false];
        yield [DateTime::class, 'date', false];
        yield [DateTime::class, 'datetime', false];
        yield ['Some text here', 'string', true];
        yield [['some', 'array', 'here'], 'array', true];
        yield [true, 'boolean', true];
        yield [true, 'bool', true];
    }
}
