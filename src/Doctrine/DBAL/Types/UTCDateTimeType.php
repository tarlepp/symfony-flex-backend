<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/UTCDateTimeType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Doctrine\DBAL\Types;

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
     * @var \DateTimeZone|null
     */
    static private $utc;

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed                                     $value    The value to convert.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (self::$utc === null) {
            self::$utc = new \DateTimeZone('UTC');
        }

        if ($value instanceof \DateTime) {
            $value->setTimezone(self::$utc);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed                                     $value    The value to convert.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The PHP representation of the value.
     *
     * @throws \Doctrine\DBAL\Types\ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (self::$utc === null) {
            self::$utc = new \DateTimeZone('UTC');
        }

        if ($value instanceof \DateTime) {
            $value->setTimezone(self::$utc);
        } elseif ($value !== null) {
            $converted = \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, self::$utc);

            if (!$converted) {
                throw ConversionException::conversionFailedFormat(
                    $value,
                    $this->getName(),
                    $platform->getDateTimeFormatString()
                );
            }

            $value = $converted;
        }

        return parent::convertToPHPValue($value, $platform);
    }
}
