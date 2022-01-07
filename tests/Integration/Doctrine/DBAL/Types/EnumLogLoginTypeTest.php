<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/EnumLogLoginTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Doctrine\DBAL\Types;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Enum\Login;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Generator;
use InvalidArgumentException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use TypeError;

/**
 * Class EnumLogLoginTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EnumLogLoginTypeTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `getSQLDeclaration` method returns expected
     */
    public function testThatGetSQLDeclarationReturnsExpected(): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame("ENUM('failure', 'success')", $type->getSQLDeclaration([], $platform));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueWorksWithProperValues
     *
     * @throws Throwable
     *
     * @testdox Test that `convertToDatabaseValue` method returns `$value`
     */
    public function testThatConvertToDatabaseValueWorksWithProperValues(string $value, Login $login): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame($value, $type->convertToDatabaseValue($login, $platform));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueThrowsAnException
     *
     * @throws Throwable
     *
     * @testdox Test that `convertToDatabaseValue` method throws an exception with `$value` input
     */
    public function testThatConvertToDatabaseValueThrowsAnException(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid \'EnumLogLogin\' value');

        $type = $this->getType();
        $platform = $this->getPlatform();

        $type->convertToDatabaseValue($value, $platform);
    }

    /**
     * @dataProvider dataProviderTestThatConvertToPHPValueMethodReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `convertToPHPValue` method returns `$expected` when using `$value`
     */
    public function testThatConvertToPHPValueMethodReturnsExpected(Login $expected, string $value): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame($expected, $type->convertToPHPValue($value, $platform));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToPHPValueMethodThrowsConversionExceptionWithInvalidValue
     *
     * @throws Throwable
     *
     * @testdox Test that `convertToPHPValue` method throws `ConversionException` exception with `$value` input
     */
    public function testThatConvertToPHPValueMethodThrowsConversionExceptionWithInvalidValue(string $value): void
    {
        $this->expectException(ConversionException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessage(
            'Could not convert database value "string" to Doctrine Type EnumLogLogin. Expected format: One of: "failure", "success"'
        );
        /* @codingStandardsIgnoreEnd */

        $type = $this->getType();
        $platform = $this->getPlatform();

        $type->convertToPHPValue($value, $platform);
    }

    /**
     * @dataProvider dataProviderTestThatConvertToPHPValueMethodThrowsTypeErrorExceptionWithInvalidValue
     *
     * @throws Throwable
     *
     * @testdox Test that `convertToPHPValue` method throws `TypeError` exception with `$value` input
     */
    public function testThatConvertToPHPValueMethodThrowsAnExceptionWithInvalidValue(mixed $value): void
    {
        $this->expectException(TypeError::class);

        $type = $this->getType();
        $platform = $this->getPlatform();

        $type->convertToPHPValue($value, $platform);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `requiresSQLCommentHint` method returns expected
     */
    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertTrue($type->requiresSQLCommentHint($platform));
    }

    /**
     * @return Generator<array{0: 'failure'|'success'}>
     */
    public function dataProviderTestThatConvertToDatabaseValueWorksWithProperValues(): Generator
    {
        yield ['failure', Login::FAILURE];
        yield ['success', Login::SUCCESS];
    }

    /**
     * @return Generator<array{0: mixed}>
     */
    public function dataProviderTestThatConvertToDatabaseValueThrowsAnException(): Generator
    {
        yield [null];
        yield [false];
        yield [true];
        yield [''];
        yield [' '];
        yield ['foobar'];
        yield [[]];
        yield [new stdClass()];
    }

    /**
     * @return Generator<array{0: Login, 1: string}>
     */
    public function dataProviderTestThatConvertToPHPValueMethodReturnsExpected(): Generator
    {
        yield [Login::SUCCESS, 'success'];
        yield [Login::FAILURE, 'failure'];
    }

    /**
     * @return Generator<array{0: mixed}>
     */
    public function dataProviderTestThatConvertToPHPValueMethodThrowsConversionExceptionWithInvalidValue(): Generator
    {
        yield [''];
        yield [' '];
        yield ['foobar'];
    }

    /**
     * @return Generator<array{0: mixed}>
     */
    public function dataProviderTestThatConvertToPHPValueMethodThrowsTypeErrorExceptionWithInvalidValue(): Generator
    {
        yield [null];
        yield [false];
        yield [true];
        yield [[]];
        yield [new stdClass()];
    }

    private function getPlatform(): AbstractPlatform
    {
        return new MySQLPlatform();
    }

    /**
     * @throws Throwable
     */
    private function getType(): Type
    {
        Type::hasType('EnumLogLogin')
            ? Type::overrideType('EnumLogLogin', EnumLogLoginType::class)
            : Type::addType('EnumLogLogin', EnumLogLoginType::class);

        return Type::getType('EnumLogLogin');
    }
}
