<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Utils/Tests/PHPUnitUtilTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit\Utils\Tests;

use App\Entity\User;
use App\Utils\Tests\PhpUnitUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PHPUnitUtilTest
 *
 * @package App\Tests\Unit\Utils\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class PHPUnitUtilTest extends KernelTestCase
{
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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot create valid value for type '666'.
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
     */
    public function testThatGetValidValueReturnsExpectedValue($expected, string $input, bool $strict): void
    {
        $value = PhpUnitUtil::getValidValueForType(PhpUnitUtil::getType($input));

        $strict ? static::assertSame($expected, $value) : static::assertInstanceOf($expected, $value);
    }

    public function testThatGetValidValueForTypeWorksWithCustomType(): void
    {
        static::assertInstanceOf(User::class, PhpUnitUtil::getValidValueForType(User::class));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot create invalid value for type '666'.
     */
    public function testThatGetInvalidValueForTypeThrowsAnExceptionWithNotKnowType(): void
    {
        PhpUnitUtil::getInvalidValueForType('666');
    }

    /**
     * @dataProvider dataProviderTestThatGetInvalidValueForTypeReturnsExpectedValue
     *
     * @param mixed  $expected
     * @param string $input
     */
    public function testThatGetInvalidValueForTypeReturnsExpectedValue($expected, string $input): void
    {
        static::assertInstanceOf($expected, PhpUnitUtil::getInvalidValueForType($input));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetInvalidValueForTypeReturnsExpectedValue(): array
    {
        return [
            [\DateTime::class, \stdClass::class],
            [\stdClass::class, User::class],
            [\stdClass::class, 'integer'],
            [\stdClass::class, \DateTime::class],
            [\stdClass::class, 'string'],
            [\stdClass::class, 'array'],
            [\stdClass::class, 'boolean'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetTypeReturnExpected(): array
    {
        return [
            ['integer', 'integer'],
            ['integer', 'bigint'],
            [\DateTime::class, 'time'],
            [\DateTime::class, 'date'],
            [\DateTime::class, 'datetime'],
            ['string', 'string'],
            ['string', 'text'],
            ['array', 'array'],
            ['boolean', 'boolean'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetValidValueReturnsExpectedValue(): array
    {
        return [
            [666, 'integer', true],
            [666, 'bigint', true],
            [\DateTime::class, 'time', false],
            [\DateTime::class, 'date', false],
            [\DateTime::class, 'datetime', false],
            ['Some text here', 'string', true],
            [['some', 'array', 'here'], 'array', true],
            [true, 'boolean', true],
        ];
    }
}
