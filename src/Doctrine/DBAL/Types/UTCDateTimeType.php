<?php
declare(strict_types = 1);

/**
 * /src/Doctrine/DBAL/Types/UTCDateTimeType.php
 */

namespace App\Doctrine\DBAL\Types;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Override;

/**
 * @see http://doctrine-orm.readthedocs.org/en/latest/cookbook/working-with-datetime.html
 */
class UTCDateTimeType extends Type
{
    private static ?DateTimeZone $utc = null;

    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
    }

    /**
     * @throws InvalidType
     */
    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof DateTime) {
            return $value->setTimezone($this->getUtcDateTimeZone())
                ->format($platform->getDateTimeFormatString());
        }

        throw InvalidType::new($value, static::class, ['null', DateTime::class]);
    }

    /**
     * @throws InvalidFormat
     */
    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTime
    {
        if ($value instanceof DateTime) {
            $value->setTimezone($this->getUtcDateTimeZone());
        } elseif ($value !== null) {
            $converted = DateTime::createFromFormat(
                $platform->getDateTimeFormatString(),
                (string)$value,
                $this->getUtcDateTimeZone(),
            );

            $value = $this->checkConvertedValue((string)$value, $platform, $converted !== false ? $converted : null);
        }

        return $value;
    }

    /**
     * Method to initialize DateTimeZone as in UTC.
     */
    private function getUtcDateTimeZone(): DateTimeZone
    {
        return self::$utc ??= new DateTimeZone('UTC');
    }

    /**
     * Method to check if conversion was successfully or not.
     *
     * @throws InvalidFormat
     */
    private function checkConvertedValue(string $value, AbstractPlatform $platform, ?DateTime $converted): DateTime
    {
        if ($converted instanceof DateTime) {
            return $converted;
        }

        throw InvalidFormat::new(
            $value,
            'datetime',
            $platform->getDateTimeFormatString(),
        );
    }
}
