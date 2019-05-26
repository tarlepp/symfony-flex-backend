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
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * @var Type
     */
    private $type;

    public function testThatGetSQLDeclarationReturnsExpected(): void
    {
        static::assertSame("ENUM('failure', 'success')", $this->type->getSQLDeclaration([], $this->platform));
    }

    /**
     * @dataProvider dataProviderTestThatConvertToDatabaseValueWorksWithProperValues
     *
     * @param string $value
     */
    public function testThatConvertToDatabaseValueWorksWithProperValues(string $value): void
    {
        static::assertSame($value, $this->type->convertToDatabaseValue($value, $this->platform));
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid 'EnumLogLogin' value
     *
     * @dataProvider dataProviderTestThatConvertToDatabaseValueThrowsAnException
     *
     * @param mixed $value
     */
    public function testThatConvertToDatabaseValueThrowsAnException($value): void
    {
        $this->type->convertToDatabaseValue($value, $this->platform);
    }

    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        static::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatConvertToDatabaseValueWorksWithProperValues(): Generator
    {
        yield ['failure'];
        yield ['success'];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatConvertToDatabaseValueThrowsAnException(): Generator
    {
        yield [null];
        yield [false];
        yield [true];
        yield [''];
        yield [' '];
        yield ['foobar'];
        yield [new stdClass()];
    }

    /**
     * @throws DBALException
     */
    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        $this->platform = new MySqlPlatform();

        if (Type::hasType('EnumLogLogin')) {
            Type::overrideType('EnumLogLogin', EnumLogLoginType::class);
        } else {
            Type::addType('EnumLogLogin', EnumLogLoginType::class);
        }

        $this->type = Type::getType('EnumLogLogin');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->type, $this->platform);

        gc_collect_cycles();
    }
}
