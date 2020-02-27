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
use Throwable;
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
    public function getLanguages(): array
    {
        return EnumLanguageType::getValues();
    }

    /**
     * @return array
     */
    public function getLocales(): array
    {
        return EnumLocaleType::getValues();
    }

    /**
     * @return array
     *
     * @throws Throwable
     */
    public function getTimeZones(): array
    {
        $output = [];

        foreach (DateTimeZone::listIdentifiers() as $timeZoneIdentifier) {
            $dateTimeZone = new DateTimeZone($timeZoneIdentifier);
            $dateTime = new DateTime('now', $dateTimeZone);

            $hours = floor($dateTimeZone->getOffset($dateTime) / 3600);
            $minutes = floor(($dateTimeZone->getOffset($dateTime) - ($hours * 3600)) / 60);

            $hours = 'GMT' . ($hours < 0 ? $hours : '+' . $hours);
            $minutes = ($minutes > 0 ? $minutes : '0' . $minutes);

            $output[] = [
                'timezone' => explode('/', $timeZoneIdentifier)[0],
                'identifier' => $timeZoneIdentifier,
                'offset' => $hours .':'. $minutes,
                'value' => str_replace('_', ' ', $timeZoneIdentifier),
            ];
        }

        return $output;
    }
}
