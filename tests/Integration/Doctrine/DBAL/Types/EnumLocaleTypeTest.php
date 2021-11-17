<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/EnumLocaleTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Doctrine\DBAL\Types;

use App\Doctrine\DBAL\Types\EnumLocaleType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Type;
use Generator;
use InvalidArgumentException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class EnumLocaleTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EnumLocaleTypeTest extends KernelTestCase
{
    /**
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
     * @testdox Test that `convertToDatabaseValue` method returns `$value`.
     */
    public function testThatConvertToDatabaseValueWorksWithProperValues(string $value): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame($value, $type->convertToDatabaseValue($value, $platform));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueThrowsAnException
     *
     * @testdox Test that `convertToDatabaseValue` method throws an exception with `$value` input
     */
    public function testThatConvertToDatabaseValueThrowsAnException(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid \'EnumLocale\' value');

        $type = $this->getType();
        $platform = $this->getPlatform();

        $type->convertToDatabaseValue($value, $platform);
    }

    /**
     * @testdox Test that `requiresSQLCommentHint` method returns expected
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
        yield ['en'];
        yield ['fi'];
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

    private function getPlatform(): AbstractPlatform
    {
        return new MySqlPlatform();
    }

    private function getType(): Type
    {
        Type::hasType('EnumLocale')
            ? Type::overrideType('EnumLocale', EnumLocaleType::class)
            : Type::addType('EnumLocale', EnumLocaleType::class);

        return Type::getType('EnumLocale');
    }
}
