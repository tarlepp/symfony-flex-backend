<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/EnumLogLoginTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Doctrine\DBAL\Types;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use Generator;
use InvalidArgumentException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class EnumLogLoginTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EnumLogLoginTypeTest extends KernelTestCase
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

        Type::hasType('EnumLogLogin')
            ? Type::overrideType('EnumLogLogin', EnumLogLoginType::class)
            : Type::addType('EnumLogLogin', EnumLogLoginType::class);

        $this->type = Type::getType('EnumLogLogin');
    }

    public function testThatGetSQLDeclarationReturnsExpected(): void
    {
        static::assertSame("ENUM('failure', 'success')", $this->type->getSQLDeclaration([], $this->platform));
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
        $this->expectExceptionMessage('Invalid \'EnumLogLogin\' value');

        $this->type->convertToDatabaseValue($value, $this->platform);
    }

    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        static::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    public function dataProviderTestThatConvertToDatabaseValueWorksWithProperValues(): Generator
    {
        yield ['failure'];
        yield ['success'];
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
