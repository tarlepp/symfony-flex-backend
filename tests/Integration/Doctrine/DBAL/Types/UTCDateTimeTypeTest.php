<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/UTCDateTimeTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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

/**
 * Class UTCDateTimeTypeTest
 *
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UTCDateTimeTypeTest extends KernelTestCase
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

        Type::hasType('datetime')
            ? Type::overrideType('datetime', UTCDateTimeType::class)
            : Type::addType('datetime', UTCDateTimeType::class);

        $this->type = Type::getType('datetime');
    }

    /**
     * @throws Throwable
     */
    public function testThatDateTimeConvertsToDatabaseValue(): void
    {
        $dateInput = new DateTime('1981-04-07 10:00:00', new DateTimeZone('Europe/Helsinki'));
        $dateExpected = clone $dateInput;

        $expected = $dateExpected
            ->setTimezone(new DateTimeZone('UTC'))
            ->format($this->platform->getDateTimeTzFormatString());

        $actual = $this->type->convertToDatabaseValue($dateInput, $this->platform);

        static::assertSame($expected, $actual);
    }

    /**
     * @throws Throwable
     */
    public function testThatConvertToDatabaseValueCreatesTimeZoneInstanceIfItIsNull(): void
    {
        PhpUnitUtil::setProperty('utc', null, $this->type);

        static::assertNull(PhpUnitUtil::getProperty('utc', $this->type));

        $dateInput = new DateTime('1981-04-07 10:00:00', new DateTimeZone('Europe/Helsinki'));

        $this->type->convertToDatabaseValue($dateInput, $this->platform);

        /** @var DateTimeZone $property */
        $property = PhpUnitUtil::getProperty('utc', $this->type);

        static::assertInstanceOf(DateTimeZone::class, $property);
        static::assertSame('UTC', $property->getName());
    }

    /**
     * @dataProvider dataProviderTestDateTimeConvertsToPHPValue
     *
     * @param string|DateTime $value
     *
     * @testdox Test that `convertToPHPValue` method converts `$value` to `$expected`.
     */
    public function testDateTimeConvertsToPHPValue(string $expected, $value): void
    {
        $date = $this->type->convertToPHPValue($value, $this->platform);

        static::assertInstanceOf('DateTime', $date);
        static::assertSame($expected, $date->format('Y-m-d H:i:s'));
    }

    /**
     * @throws ReflectionException
     */
    public function testThatConvertToPHPValueCreatesTimeZoneInstanceIfItIsNull(): void
    {
        PhpUnitUtil::setProperty('utc', null, $this->type);

        static::assertNull(PhpUnitUtil::getProperty('utc', $this->type));

        $this->type->convertToPHPValue('1981-04-07 10:00:00', $this->platform);

        /** @var DateTimeZone $property */
        $property = PhpUnitUtil::getProperty('utc', $this->type);

        static::assertInstanceOf(DateTimeZone::class, $property);
        static::assertSame('UTC', $property->getName());
    }

    public function testThatConvertToPHPValueThrowsAnExceptionWithInvalidValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('foobar', $this->platform);
    }

    public function testThatRequiresSQLCommentHintReturnsExpected(): void
    {
        static::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    /**
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
}
