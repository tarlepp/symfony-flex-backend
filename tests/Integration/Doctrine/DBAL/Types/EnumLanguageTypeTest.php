<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/EnumLanguageTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Doctrine\DBAL\Types;

use App\Doctrine\DBAL\Types\EnumLanguageType;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use Generator;
use InvalidArgumentException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class EnumLanguageTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EnumLanguageTypeTest extends KernelTestCase
{
    private AbstractPlatform $platform;
    private Type $type;

    /**
     * @throws DBALException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->platform = new MySqlPlatform();

        Type::hasType('EnumLanguage')
            ? Type::overrideType('EnumLanguage', EnumLanguageType::class)
            : Type::addType('EnumLanguage', EnumLanguageType::class);

        $this->type = Type::getType('EnumLanguage');
    }

    public function testThatGetSQLDeclarationReturnsExpected(): void
    {
        static::assertSame("ENUM('en', 'fi')", $this->type->getSQLDeclaration([], $this->platform));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueWorksWithProperValues
     *
     * @testdox Test that `convertToDatabaseValue` method returns `$value`.
     */
    public function testThatConvertToDatabaseValueWorksWithProperValues(string $value): void
    {
        static::assertSame($value, $this->type->convertToDatabaseValue($value, $this->platform));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueThrowsAnException
     *
     * @param mixed $value
     *
     * @testdox Test that `convertToDatabaseValue` method throws an exception with `$value` input.
     */
    public function testThatConvertToDatabaseValueThrowsAnException($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid \'EnumLanguage\' value');

        $this->type->convertToDatabaseValue($value, $this->platform);
    }

    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        static::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    public function dataProviderTestThatConvertToDatabaseValueWorksWithProperValues(): Generator
    {
        yield ['en'];
        yield ['fi'];
    }

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
}
