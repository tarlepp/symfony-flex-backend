<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/UTCDateTimeType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Doctrine\DBAL\Types;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\DateTimeType;
use Override;

/**
 * @see http://doctrine-orm.readthedocs.org/en/latest/cookbook/working-with-datetime.html
 *
 * @package App\Doctrine\DBAL\Types
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UTCDateTimeType extends DateTimeType
{
    private static ?DateTimeZone $utc = null;

    /**
     * {@inheritdoc}
     *
     * @throws InvalidFormat
     */
    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        if ($value instanceof DateTime) {
            $value->setTimezone($this->getUtcDateTimeZone());
        }

        return (string)parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * @param T $value
     *
     * @return (T is null ? null : DateTime)
     *
     * @template T
     *
     * @throws InvalidFormat
     * @throws Exception
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
                $this->getUtcDateTimeZone()
            );

            $value = $this->checkConvertedValue((string)$value, $platform, $converted !== false ? $converted : null);
        }

        return parent::convertToPHPValue($value, $platform);
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
     * @throws Exception
     */
    private function checkConvertedValue(string $value, AbstractPlatform $platform, ?DateTime $converted): DateTime
    {
        if ($converted instanceof DateTime) {
            return $converted;
        }

        throw InvalidFormat::new(
            $value,
            self::lookupName($this),
            $platform->getDateTimeFormatString()
        );
    }
}
