<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Doctrine/DBAL/Types/UTCDateTimeTypeTest.php
 */

namespace App\Tests\Integration\Doctrine\DBAL\Types;

use App\Doctrine\DBAL\Types\UTCDateTimeType;
use App\Tests\Utils\PhpUnitUtil;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

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
        self::assertSame('UTC', $dateInput->getTimezone()->getName());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSQLDeclarationReturnsPlatformDateTimeDeclaration(): void
    {
        $platform = $this->getPlatform();

        self::assertSame(
            $platform->getDateTimeTypeDeclarationSQL([]),
            $this->getType()->getSQLDeclaration([], $platform),
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatNullConversionPreservesExistingBehavior(): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        self::assertSame('', $type->convertToDatabaseValue(null, $platform));
        self::assertNull($type->convertToPHPValue(null, $platform));
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderInvalidDatabaseValues')]
    public function testThatConvertToDatabaseValueRejectsInvalidTypes(mixed $value): void
    {
        $this->expectException(InvalidType::class);

        $this->getType()->convertToDatabaseValue($value, $this->getPlatform());
    }

    /**
     * @throws Throwable
     */
    public function testThatConvertToPHPValuePreservesMutableDateTimeIdentity(): void
    {
        $date = new DateTime('1981-04-07 10:00:00', new DateTimeZone('Europe/Helsinki'));

        self::assertSame($date, $this->getType()->convertToPHPValue($date, $this->getPlatform()));
        self::assertSame('UTC', $date->getTimezone()->getName());
        self::assertSame('1981-04-07 07:00:00', $date->format('Y-m-d H:i:s'));
    }

    /**
     * @return Generator<int, array{0: mixed}>
     */
    public static function dataProviderInvalidDatabaseValues(): Generator
    {
        yield ['1981-04-07 10:00:00'];
        yield [false];
        yield [42];
        yield [[]];
        yield [new DateTimeImmutable()];
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
    public function testDateTimeConvertsToPHPValue(string $expected, string|DateTime $value): void
    {
        $type = $this->getType();
        $platform = $this->getPlatform();

        $date = $type->convertToPHPValue($value, $platform);

        self::assertInstanceOf(DateTime::class, $date);
        self::assertSame('UTC', $date->getTimezone()->getName());
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
     * @return Generator<int, array{0: string, 1: string|DateTime}>
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
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);

        $type = $connection->getConfiguration()->getTypeProvider()->get('datetime');

        self::assertInstanceOf(UTCDateTimeType::class, $type);

        return $type;
    }
}
