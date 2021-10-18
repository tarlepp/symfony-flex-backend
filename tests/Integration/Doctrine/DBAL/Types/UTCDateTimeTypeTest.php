<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/UTCDateTimeTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Doctrine\DBAL\Types;

use App\Doctrine\DBAL\Types\UTCDateTimeType;
use App\Utils\Tests\PhpUnitUtil;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Generator;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use UnexpectedValueException;

/**
 * Class UTCDateTimeTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UTCDateTimeTypeTest extends KernelTestCase
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

        Type::hasType('datetime')
            ? Type::overrideType('datetime', UTCDateTimeType::class)
            : Type::addType('datetime', UTCDateTimeType::class);

        $this->type = Type::getType('datetime');
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `convertToDatabaseValue` method works as expected
     */
    public function testThatDateTimeConvertsToDatabaseValue(): void
    {
        $dateInput = new DateTime('1981-04-07 10:00:00', new DateTimeZone('Europe/Helsinki'));
        $dateExpected = clone $dateInput;

        $expected = $dateExpected
            ->setTimezone(new DateTimeZone('UTC'))
            ->format($this->getPlatform()->getDateTimeTzFormatString());

        self::assertSame($expected, $this->getType()->convertToDatabaseValue($dateInput, $this->getPlatform()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `convertToDatabaseValue` method creates DateTimeZone instance as expected
     */
    public function testThatConvertToDatabaseValueCreatesTimeZoneInstanceIfItIsNull(): void
    {
        $type = $this->getType();

        PhpUnitUtil::setProperty('utc', null, $type);

        self::assertNull(PhpUnitUtil::getProperty('utc', $type));

        $dateInput = new DateTime('1981-04-07 10:00:00', new DateTimeZone('Europe/Helsinki'));

        $type->convertToDatabaseValue($dateInput, $this->getPlatform());

        /** @var DateTimeZone $property */
        $property = PhpUnitUtil::getProperty('utc', $type);

        self::assertInstanceOf(DateTimeZone::class, $property);
        self::assertSame('UTC', $property->getName());
    }

    /**
     * @dataProvider dataProviderTestDateTimeConvertsToPHPValue
     *
     * @testdox Test that `convertToPHPValue` method converts `$value` to `$expected`
     */
    public function testDateTimeConvertsToPHPValue(string $expected, string | DateTime $value): void
    {
        $date = $this->getType()->convertToPHPValue($value, $this->getPlatform());

        self::assertInstanceOf(DateTime::class, $date);
        self::assertSame($expected, $date->format('Y-m-d H:i:s'));
    }

    /**
     * @throws ReflectionException
     *
     * @testdox Test that `convertToPHPValue` method creates DateTimeZone instance as expected
     */
    public function testThatConvertToPHPValueCreatesTimeZoneInstanceIfItIsNull(): void
    {
        $type = $this->getType();

        PhpUnitUtil::setProperty('utc', null, $type);

        self::assertNull(PhpUnitUtil::getProperty('utc', $type));

        $type->convertToPHPValue('1981-04-07 10:00:00', $this->getPlatform());

        /** @var DateTimeZone $property */
        $property = PhpUnitUtil::getProperty('utc', $type);

        self::assertInstanceOf(DateTimeZone::class, $property);
        self::assertSame('UTC', $property->getName());
    }

    /**
     * @testdox Test that `convertToPHPValue` method throws an exception when invalid value is used
     */
    public function testThatConvertToPHPValueThrowsAnExceptionWithInvalidValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->getType()->convertToPHPValue('foobar', $this->getPlatform());
    }

    /**
     * @testdox Test that `requiresSQLCommentHint` method returns expected
     */
    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        self::assertTrue($this->getType()->requiresSQLCommentHint($this->getPlatform()));
    }

    /**
     * @return Generator<array{0: string, 1: string|DateTime}>
     *
     * @throws Throwable
     */
    public function dataProviderTestDateTimeConvertsToPHPValue(): Generator
    {
        yield [
            '1981-04-07 10:00:00',
            '1981-04-07 10:00:00',
        ];

        yield [
            '1981-04-07 07:00:00',
            new DateTime('1981-04-07 10:00:00', new DateTimeZone('Europe/Helsinki')),
        ];

        yield [
            '1981-04-07 10:00:00',
            new DateTime('1981-04-07 10:00:00', new DateTimeZone('UTC')),
        ];
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
