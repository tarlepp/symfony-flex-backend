<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/UTCDateTimeType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Doctrine\DBAL\Types;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * Class UTCDateTimeType
 *
 * @see http://doctrine-orm.readthedocs.org/en/latest/cookbook/working-with-datetime.html
 *
 * @package App\Doctrine\DBAL\Types
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UTCDateTimeType extends DateTimeType
{
    private static ?DateTimeZone $utc;

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed            $value    the value to convert
     * @param AbstractPlatform $platform the currently used database platform
     *
     * @return string the database representation of the value
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        if ($value instanceof DateTime) {
            $value->setTimezone($this->getUtcDateTimeZone());
        }

        return (string)parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed            $value    the value to convert
     * @param AbstractPlatform $platform the currently used database platform
     *
     * @return mixed the PHP representation of the value
     *
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
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
     * {@inheritdoc}
     *
     * @param AbstractPlatform $platform
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        parent::requiresSQLCommentHint($platform);

        return true;
    }

    /**
     * Method to initialize DateTimeZone as in UTC.
     *
     * @return DateTimeZone
     */
    private function getUtcDateTimeZone(): DateTimeZone
    {
        return self::$utc ??= new DateTimeZone('UTC');
    }

    /**
     * Method to check if conversion was successfully or not.
     *
     * @param string           $value
     * @param AbstractPlatform $platform
     * @param DateTime|null    $converted
     *
     * @return DateTime
     *
     * @throws ConversionException
     */
    private function checkConvertedValue(string $value, AbstractPlatform $platform, ?DateTime $converted): DateTime
    {
        if ($converted instanceof DateTime) {
            return $converted;
        }

        throw ConversionException::conversionFailedFormat(
            $value,
            $this->getName(),
            $platform->getDateTimeFormatString()
        );
    }
}
