<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Utils/Tests/PHPUnitUtilTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Utils\Tests;

use App\Entity\User;
use App\Tests\Utils\PhpUnitUtil;
use App\Tests\Utils\StringableArrayObject;
use DateTime;
use DateTimeImmutable;
use Generator;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function is_string;

/**
 * @package App\Tests\Unit\Utils\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class PHPUnitUtilTest extends KernelTestCase
{
    #[TestDox('Test that `getType` method throws exception with not know type')]
    public function testThatGetTypeThrowsAnExceptionWithNotKnowType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Currently type \'666\' is not supported within type normalizer');

        PhpUnitUtil::getType('666');
    }

    #[DataProvider('dataProviderTestThatGetTypeReturnExpected')]
    #[TestDox('Test that `getType` method returns `$expected` when using `$input` as input')]
    public function testThatGetTypeReturnExpected(string $expected, string $input): void
    {
        self::assertSame($expected, PhpUnitUtil::getType($input));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getValidValueForType` method throws exception with not know type')]
    public function testThatGetValidValueForTypeThrowsAnExceptionWithNotKnowType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot create valid value for type \'666\'.');

        PhpUnitUtil::getValidValueForType('666');
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetValidValueReturnsExpectedValue')]
    #[TestDox('Test that `getValidValueForType` method returns `$expected` with `$input` and strict mode `$strict`')]
    public function testThatGetValidValueReturnsExpectedValue(mixed $expected, string $input, bool $strict): void
    {
        $value = PhpUnitUtil::getValidValueForType(PhpUnitUtil::getType($input));

        /** @var StringableArrayObject|mixed $expected */
        $expected = $expected instanceof StringableArrayObject ? $expected->getArrayCopy() : $expected;

        if ($strict) {
            self::assertSame($expected, $value);
        } elseif (is_string($expected)) {
            /** @var class-string $expected */
            self::assertInstanceOf($expected, $value);
        } else {
            throw new LogicException('This should not happen...');
        }
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getValidValueForType` works as expected with custom type')]
    public function testThatGetValidValueForTypeWorksWithCustomType(): void
    {
        self::assertInstanceOf(User::class, PhpUnitUtil::getValidValueForType(User::class));
    }

    /**
     * @param int|string|array<int, string> $expected
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetValidValueForTypeWorksIfThereIsAPipeOnType')]
    #[TestDox('Test that `getValidValueForType` returns `$expected` when using `$type` as input type')]
    public function testThatGetValidValueForTypeWorksIfThereIsAPipeOnType(
        int | string | array $expected,
        string $type,
    ): void {
        self::assertSame($expected, PhpUnitUtil::getValidValueForType($type));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getInvalidValueForType` method throws exception with not know type')]
    public function testThatGetInvalidValueForTypeThrowsAnExceptionWithNotKnowType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot create invalid value for type \'666\'.');

        PhpUnitUtil::getInvalidValueForType('666');
    }

    /**
     * @param class-string $expected
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetInvalidValueForTypeReturnsExpectedValue')]
    #[TestDox('Test that `getInvalidValueForType` method returns `$expected` when using `$input` as input')]
    public function testThatGetInvalidValueForTypeReturnsExpectedValue(string $expected, string $input): void
    {
        self::assertInstanceOf($expected, PhpUnitUtil::getInvalidValueForType($input));
    }

    /**
     * @return Generator<array<int, mixed>>
     */
    public static function dataProviderTestThatGetInvalidValueForTypeReturnsExpectedValue(): Generator
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
        yield [stdClass::class, 'string|int'];
        yield [stdClass::class, 'string[]'];
    }

    /**
     * @return Generator<array<int, mixed>>
     */
    public static function dataProviderTestThatGetTypeReturnExpected(): Generator
    {
        yield ['int', 'integer'];
        yield ['int', 'int'];
        yield ['string', 'bigint'];
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
     * @return Generator<array<int, mixed>>
     */
    public static function dataProviderTestThatGetValidValueReturnsExpectedValue(): Generator
    {
        yield [666, 'int', true];
        yield [666, 'integer', true];
        yield ['Some text here', 'bigint', true];
        yield [DateTime::class, 'time', false];
        yield [DateTime::class, 'date', false];
        yield [DateTime::class, 'datetime', false];
        yield [DateTimeImmutable::class, 'time_immutable', false];
        yield [DateTimeImmutable::class, 'date_immutable', false];
        yield [DateTimeImmutable::class, 'datetime_immutable', false];
        yield ['Some text here', 'string', true];
        yield [new StringableArrayObject(['some', 'array', 'here']), 'array', true];
        yield [true, 'boolean', true];
        yield [true, 'bool', true];
    }

    /**
     * @psalm-return Generator<array{0: int|string|array, 1: string}>
     * @phpstan-return Generator<array{0: int|string|array<mixed>, 1: string}>
     */
    public static function dataProviderTestThatGetValidValueForTypeWorksIfThereIsAPipeOnType(): Generator
    {
        yield ['Some text here', 'string|int'];
        yield [666, 'int|string'];
        yield [['some', 'array', 'here'], 'string[]'];
    }
}
