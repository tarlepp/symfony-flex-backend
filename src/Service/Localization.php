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
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
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
    public const DEFAULT_LANGUAGE = EnumLanguageType::LANGUAGE_EN;
    public const DEFAULT_LOCALE = EnumLocaleType::LOCALE_EN;
    public const DEFAULT_TIMEZONE = 'Europe/Helsinki';

    private CacheInterface $cache;
    private LoggerInterface $logger;

    /**
     * Localization constructor.
     *
     * @param CacheInterface  $appCacheApcu
     * @param LoggerInterface $logger
     */
    public function __construct(CacheInterface $appCacheApcu, LoggerInterface $logger)
    {
        $this->cache = $appCacheApcu;
        $this->logger = $logger;
    }

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

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @return array
     */
    public function getTimezones(): array
    {
        $output = [];

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $output = $this->cache->get('application_timezone', function (ItemInterface $item): array {
                $item->expiresAfter(31536000); // One year

                return $this->getFormattedTimezones();
            });
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return $output;
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @return array
     */
    public function getFormattedTimezones(): array
    {
        $output = [];

        foreach (DateTimeZone::listIdentifiers() as $identifier) {
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
}
