<?php
declare(strict_types=1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/EnumLogLoginTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
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
     * @var \Doctrine\DBAL\Types\Type
     */
    private $type;

    public function setUp()
    {
        parent::setUp();

        $this->platform = new MySqlPlatform();
        $this->type = Type::getType('enumLogLogin');
    }

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

    /**
     * @return array
     */
    public function dataProviderTestThatConvertToDatabaseValueWorksWithProperValues(): array
    {
        return [
            ['failure'],
            ['success'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatConvertToDatabaseValueThrowsAnException(): array
    {
        return [
            [null],
            [false],
            [true],
            [''],
            [' '],
            ['foobar'],
            [new \stdClass()],
        ];
    }
}
