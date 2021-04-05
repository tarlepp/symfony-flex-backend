<?php
declare(strict_types = 1);
/**
 * /src/Service/Localization.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Service;

use App\Doctrine\DBAL\Types\EnumLanguageType;
use App\Doctrine\DBAL\Types\EnumLocaleType;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;
use function explode;
use function floor;
use function str_replace;

/**
 * Class Localization
 *
 * @package App\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class Localization
{
    public const DEFAULT_LANGUAGE = EnumLanguageType::LANGUAGE_EN;
    public const DEFAULT_LOCALE = EnumLocaleType::LOCALE_EN;
    public const DEFAULT_TIMEZONE = 'Europe/Helsinki';

    public function __construct(
        private CacheInterface $appCacheApcu,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function getLanguages(): array
    {
        return EnumLanguageType::getValues();
    }

    /**
     * @return array<int, string>
     */
    public function getLocales(): array
    {
        return EnumLocaleType::getValues();
    }

    /**
     * @return array<int, array<string, string>>
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getTimezones(): array
    {
        $output = [];

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $output = $this->appCacheApcu->get('application_timezone', $this->getClosure());
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return $output;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @return array<int, array<string, string>>
     */
    public function getFormattedTimezones(): array
    {
        $output = [];

        $identifiers = DateTimeZone::listIdentifiers();

        foreach ($identifiers as $identifier) {
            $dateTimeZone = new DateTimeZone($identifier);

            /** @noinspection PhpUnhandledExceptionInspection */
            $dateTime = new DateTimeImmutable(timezone: $dateTimeZone);

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

    private function getClosure(): Closure
    {
        return function (ItemInterface $item): array {
            // One year
            $item->expiresAfter(31536000);

            return $this->getFormattedTimezones();
        };
    }
}
