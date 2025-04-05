<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Doctrine/DBAL/Types/UTCDateTimeTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Doctrine\DBAL\Types;

use App\Doctrine\DBAL\Types\UTCDateTimeType;
use App\Tests\Utils\PhpUnitUtil;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Integration\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UTCDateTimeTypeTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `convertToDatabaseValue` method works as expected')]
    public function testThatDateTimeConvertsToDatabaseValue(): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        $dateInput = new DateTime('1981-04-07 10:00:00', new DateTimeZone('Europe/Helsinki'));
        $dateExpected = clone $dateInput;

        $expected = $dateExpected
            ->setTimezone(new DateTimeZone('UTC'))
            ->format($platform->getDateTimeTzFormatString());

        self::assertSame($expected, $type->convertToDatabaseValue($dateInput, $platform));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `convertToDatabaseValue` method creates DateTimeZone instance as expected')]
    public function testThatConvertToDatabaseValueCreatesTimeZoneInstanceIfItIsNull(): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        PhpUnitUtil::setProperty('utc', null, $type);

        self::assertNull(PhpUnitUtil::getProperty('utc', $type));

        $dateInput = new DateTime('1981-04-07 10:00:00', new DateTimeZone('Europe/Helsinki'));

        $type->convertToDatabaseValue($dateInput, $platform);

        /** @var DateTimeZone $property */
        $property = PhpUnitUtil::getProperty('utc', $type); // @phpstan-ignore-line

        self::assertSame('UTC', $property->getName());
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestDateTimeConvertsToPHPValue')]
    #[TestDox('Test that `convertToPHPValue` method converts `$value` to `$expected`')]
    public function testDateTimeConvertsToPHPValue(string $expected, string | DateTime $value): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        $date = $type->convertToPHPValue($value, $platform);

        self::assertInstanceOf(DateTime::class, $date);
        self::assertSame($expected, $date->format('Y-m-d H:i:s'));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `convertToPHPValue` method creates DateTimeZone instance as expected')]
    public function testThatConvertToPHPValueCreatesTimeZoneInstanceIfItIsNull(): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        PhpUnitUtil::setProperty('utc', null, $type);

        self::assertNull(PhpUnitUtil::getProperty('utc', $type));

        $type->convertToPHPValue('1981-04-07 10:00:00', $platform);

        /** @var DateTimeZone $property */
        $property = PhpUnitUtil::getProperty('utc', $type); // @phpstan-ignore-line

        self::assertSame('UTC', $property->getName());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `convertToPHPValue` method throws an exception when invalid value is used')]
    public function testThatConvertToPHPValueThrowsAnExceptionWithInvalidValue(): void
    {
        $this->expectException(ConversionException::class);

        $type = $this->getType();
        $platform = $this->getPlatform();

        $type->convertToPHPValue('foobar', $platform);
    }

    /**
     * @throws Throwable
     *
     * @return Generator<array{0: string, 1: string|DateTime}>
     */
    public static function dataProviderTestDateTimeConvertsToPHPValue(): Generator
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
        return new MySQLPlatform();
    }

    /**
     * @throws Throwable
     */
    private function getType(): UTCDateTimeType
    {
        Type::hasType('datetime')
            ? Type::overrideType('datetime', UTCDateTimeType::class)
            : Type::addType('datetime', UTCDateTimeType::class);

        $type = Type::getType('datetime');

        self::assertInstanceOf(UTCDateTimeType::class, $type);

        return $type;
    }
}
