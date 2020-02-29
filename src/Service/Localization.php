<?php
declare(strict_types = 1);
/**
 * /src/Service/Localization.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Service;

use App\Doctrine\DBAL\Types\EnumLanguageType;
use App\Doctrine\DBAL\Types\EnumLocaleType;
use DateTime;
use DateTimeZone;
use function explode;
use function floor;
use function str_replace;

/**
 * Class Localization
 *
 * @package App\Service
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Localization
{
    /**
     * @return array
     */
    public static function getLanguages(): array
    {
        return EnumLanguageType::getValues();
    }

    /**
     * @return array
     */
    public static function getLocales(): array
    {
        return EnumLocaleType::getValues();
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     *
     * @return array
     */
    public static function getTimeZones(): array
    {
        $output = [];

        foreach (self::getTimeZoneIdentifiers() as $identifier) {
            $dateTimeZone = new DateTimeZone($identifier);
            $dateTime = new DateTime('now', $dateTimeZone);

            $hours = floor($dateTimeZone->getOffset($dateTime) / 3600);
            $minutes = floor(($dateTimeZone->getOffset($dateTime) - ($hours * 3600)) / 60);

            $hours = 'GMT' . ($hours < 0 ? $hours : '+' . $hours);
            $minutes = ($minutes > 0 ? $minutes : '0' . $minutes);

            $output[] = [
                'timezone' => explode('/', $identifier)[0],
                'identifier' => $identifier,
                'offset' => $hours . ':' . $minutes,
                'value' => str_replace('_', ' ', $identifier),
            ];
        }

        return $output;
    }

    /**
     * @return array
     */
    public static function getTimeZoneIdentifiers(): array
    {
        return DateTimeZone::listIdentifiers();
    }
}
