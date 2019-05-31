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
    /**
     * UTC date time zone object.
     *
     * @var DateTimeZone|null
     */
    private static $utc;

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed            $value    The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The database representation of the value.
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof DateTime) {
            $value->setTimezone($this->getUtcDateTimeZone());
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed            $value    The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The PHP representation of the value.
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
                $value,
                $this->getUtcDateTimeZone()
            );

            $this->checkConvertedValue($value, $platform, $converted);

            $value = $converted;
        }

        return parent::convertToPHPValue($value, $platform);
    }

    /**
     * @inheritDoc
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
        if (self::$utc === null) {
            self::$utc = new DateTimeZone('UTC');
        }

        return self::$utc;
    }

    /**
     * Method to check if conversion was successfully or not.
     *
     * @param mixed            $value
     * @param AbstractPlatform $platform
     * @param DateTime|bool    $converted
     *
     * @throws ConversionException
     */
    private function checkConvertedValue($value, AbstractPlatform $platform, $converted): void
    {
        if ($converted instanceof DateTime) {
            return;
        }

        throw ConversionException::conversionFailedFormat(
            $value,
            $this->getName(),
            $platform->getDateTimeFormatString()
        );
    }
}
