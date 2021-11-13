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
use UnexpectedValueException;

/**
 * Class EnumLanguageTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EnumLanguageTypeTest extends KernelTestCase
{
    private ?AbstractPlatform $platform = null;
    private ?Type $type = null;

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

    /**
     * @testdox Test that `getSQLDeclaration` method returns expected
     */
    public function testThatGetSQLDeclarationReturnsExpected(): void
    {
        self::assertSame("ENUM('en', 'fi')", $this->getType()->getSQLDeclaration([], $this->getPlatform()));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueWorksWithProperValues
     *
     * @testdox Test that `convertToDatabaseValue` method returns `$value`
     */
    public function testThatConvertToDatabaseValueWorksWithProperValues(string $value): void
    {
        self::assertSame($value, $this->getType()->convertToDatabaseValue($value, $this->getPlatform()));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueThrowsAnException
     *
     * @testdox Test that `convertToDatabaseValue` method throws an exception with `$value` input
     */
    public function testThatConvertToDatabaseValueThrowsAnException(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid \'EnumLanguage\' value');

        $this->getType()->convertToDatabaseValue($value, $this->getPlatform());
    }

    /**
     * @testdox Test that `requiresSQLCommentHint` method returns expected
     */
    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        self::assertTrue($this->getType()->requiresSQLCommentHint($this->getPlatform()));
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
        return $this->platform ?? throw new UnexpectedValueException('Platform not set');
    }

    private function getType(): Type
    {
        return $this->type ?? throw new UnexpectedValueException('Type not set');
    }
}
