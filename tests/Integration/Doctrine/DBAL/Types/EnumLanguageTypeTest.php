<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/EnumLanguageTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Doctrine\DBAL\Types;

use App\Doctrine\DBAL\Types\EnumLanguageType;
use App\Enum\Language;
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
 * Class EnumLanguageTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EnumLanguageTypeTest extends KernelTestCase
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

        self::assertSame("ENUM('en', 'fi')", $type->getSQLDeclaration([], $platform));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueWorksWithProperValues
     *
     * @throws Throwable
     *
     * @testdox Test that `convertToDatabaseValue` method returns `$value` when using `$language`
     */
    public function testThatConvertToDatabaseValueWorksWithProperValues(string $value, Language $language): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame($value, $type->convertToDatabaseValue($language, $platform));
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
        $this->expectExceptionMessage('Invalid \'EnumLanguage\' value');

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
    public function testThatConvertToPHPValueMethodReturnsExpected(Language $expected, string $value): void
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
            'Could not convert database value "string" to Doctrine Type EnumLanguage. Expected format: One of: "en", "fi"'
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
    public function testThatConvertToPHPValueMethodThrowsTypeErrorExceptionWithInvalidValue(mixed $value): void
    {
        $this->expectException(TypeError::class);

        $type = $this->getType();
        $platform = $this->getPlatform();

        $type->convertToPHPValue($value, $platform);
    }

    /**
     * @testdox Test that `requiresSQLCommentHint` method returns expected
     *
     * @throws Throwable
     */
    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertTrue($type->requiresSQLCommentHint($platform));
    }

    /**
     * @return Generator<array{0: 'en'|'fi'}>
     */
    public function dataProviderTestThatConvertToDatabaseValueWorksWithProperValues(): Generator
    {
        yield ['en', Language::EN];
        yield ['fi', Language::FI];
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
     * @return Generator<array{0: Language, 1: string}>
     */
    public function dataProviderTestThatConvertToPHPValueMethodReturnsExpected(): Generator
    {
        yield [Language::EN, 'en'];
        yield [Language::FI, 'fi'];
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
        Type::hasType('EnumLanguage')
            ? Type::overrideType('EnumLanguage', EnumLanguageType::class)
            : Type::addType('EnumLanguage', EnumLanguageType::class);

        return Type::getType('EnumLanguage');
    }
}
