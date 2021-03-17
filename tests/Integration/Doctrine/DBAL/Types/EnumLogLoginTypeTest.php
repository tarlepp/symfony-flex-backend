<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/EnumLogLoginTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use UnexpectedValueException;

/**
 * Class EnumLogLoginTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EnumLogLoginTypeTest extends KernelTestCase
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

        Type::hasType('EnumLogLogin')
            ? Type::overrideType('EnumLogLogin', EnumLogLoginType::class)
            : Type::addType('EnumLogLogin', EnumLogLoginType::class);

        $this->type = Type::getType('EnumLogLogin');
    }

    /**
     * @testdox Test that `getSQLDeclaration` method returns expected
     */
    public function testThatGetSQLDeclarationReturnsExpected(): void
    {
        static::assertSame("ENUM('failure', 'success')", $this->getType()->getSQLDeclaration([], $this->getPlatform()));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueWorksWithProperValues
     *
     * @testdox Test that `convertToDatabaseValue` method returns `$value`
     */
    public function testThatConvertToDatabaseValueWorksWithProperValues(string $value): void
    {
        static::assertSame($value, $this->getType()->convertToDatabaseValue($value, $this->getPlatform()));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueThrowsAnException
     *
     * @testdox Test that `convertToDatabaseValue` method throws an exception with `$value` input
     */
    public function testThatConvertToDatabaseValueThrowsAnException(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid \'EnumLogLogin\' value');

        $this->getType()->convertToDatabaseValue($value, $this->getPlatform());
    }

    /**
     * @testdox Test that `requiresSQLCommentHint` method returns expected
     */
    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        static::assertTrue($this->getType()->requiresSQLCommentHint($this->getPlatform()));
    }

    /**
     * @return Generator<array{0: 'failure'|'success'}>
     */
    public function dataProviderTestThatConvertToDatabaseValueWorksWithProperValues(): Generator
    {
        yield ['failure'];
        yield ['success'];
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

    private function getPlatform(): MySqlPlatform
    {
        return $this->platform instanceof MySqlPlatform
            ? $this->platform
            : throw new UnexpectedValueException('Platform not set');
    }

    private function getType(): Type
    {
        return $this->type instanceof Type
            ? $this->type
            : throw new UnexpectedValueException('Type not set');
    }
}
