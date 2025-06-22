<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/EnumLanguageTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class EnumLanguageTypeTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getSQLDeclaration` method returns expected')]
    public function testThatGetSQLDeclarationReturnsExpected(): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame("ENUM('en', 'fi')", $type->getSQLDeclaration([], $platform));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatConvertToDatabaseValueWorksWithProperValues')]
    #[TestDox('Test that `convertToDatabaseValue` method returns `$expected` when using `$language`')]
    public function testThatConvertToDatabaseValueWorksWithProperValues(string $expected, Language $language): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame($expected, $type->convertToDatabaseValue($language, $platform));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatConvertToDatabaseValueThrowsAnException')]
    #[TestDox('Test that `convertToDatabaseValue` method throws an exception with `$value` input')]
    public function testThatConvertToDatabaseValueThrowsAnException(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid \'EnumLanguage\' value');

        $type = $this->getType();
        $platform = $this->getPlatform();

        $type->convertToDatabaseValue($value, $platform);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatConvertToDatabaseValueReturnsExpectedWithStringInput')]
    #[TestDox('Test that `convertToDatabaseValue` method returns `$expected` when using `$input` as a string input')]
    public function testThatConvertToDatabaseValueReturnsExpectedWithStringInput(
        Language $expected,
        string $input,
    ): void {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame($expected->value, $type->convertToDatabaseValue($input, $platform));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatConvertToPHPValueWorksWithValidInput')]
    #[TestDox('Test that `convertToPHPValue` method returns `$expected` when using `$input`')]
    public function testThatConvertToPHPValueWorksWithValidInput(Language $expected, string $input): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame($expected, $type->convertToPHPValue($input, $platform));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatConvertToPHPValueThrowsAnException')]
    #[TestDox('Test that `convertToPHPValue` method throws an exception with `$value` input')]
    public function testThatConvertToPHPValueThrowsAnException(mixed $value): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Could not convert database value');

        $type = $this->getType();
        $platform = $this->getPlatform();

        $type->convertToPHPValue($value, $platform);
    }

    /**
     * @return Generator<array{0: 'en'|'fi', 1: Language}>
     */
    public static function dataProviderTestThatConvertToDatabaseValueWorksWithProperValues(): Generator
    {
        yield ['en', Language::EN];
        yield ['fi', Language::FI];
    }

    /**
     * @return Generator<array{0: mixed}>
     */
    public static function dataProviderTestThatConvertToDatabaseValueThrowsAnException(): Generator
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
     * @return Generator<array{0: Language, 1: 'en'|'fi'}>
     */
    public static function dataProviderTestThatConvertToDatabaseValueReturnsExpectedWithStringInput(): Generator
    {
        yield [Language::EN, 'en'];
        yield [Language::FI, 'fi'];
    }

    /**
     * @return Generator<array{0: Language, 1: 'en'|'fi'}>
     */
    public static function dataProviderTestThatConvertToPHPValueWorksWithValidInput(): Generator
    {
        yield [Language::EN, 'en'];
        yield [Language::FI, 'fi'];
    }

    /**
     * @return Generator<array{0: mixed}>
     */
    public static function dataProviderTestThatConvertToPHPValueThrowsAnException(): Generator
    {
        yield [null];
        yield [false];
        yield [true];
        yield [''];
        yield [' '];
        yield [1];
        yield ['foobar'];
    }

    private function getPlatform(): AbstractPlatform
    {
        return new MySQLPlatform();
    }

    /**
     * @throws Throwable
     */
    private function getType(): EnumLanguageType
    {
        Type::hasType('EnumLanguage')
            ? Type::overrideType('EnumLanguage', EnumLanguageType::class)
            : Type::addType('EnumLanguage', EnumLanguageType::class);

        $type = Type::getType('EnumLanguage');

        self::assertInstanceOf(EnumLanguageType::class, $type);

        return $type;
    }
}
