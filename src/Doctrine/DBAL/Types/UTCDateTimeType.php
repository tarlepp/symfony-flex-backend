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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UTCDateTimeType extends DateTimeType
{
    private static ?DateTimeZone $utc;

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        parent::requiresSQLCommentHint($platform);

        return true;
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
