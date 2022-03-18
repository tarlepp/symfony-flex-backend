<?php
declare(strict_types = 1);
/**
 * /src/Service/Localization.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Service;

use App\Enum\Language;
use App\Enum\Locale;
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
    public const DEFAULT_TIMEZONE = 'Europe/Helsinki';

    public function __construct(
        private CacheInterface $appCacheApcu,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, Language>
     */
    public function getLanguages(): array
    {
        return Language::cases();
    }

    /**
     * @return array<int, string>
     */
    public function getLanguageValues(): array
    {
        return Language::getValues();
    }

    /**
     * @return array<int, Locale>
     */
    public function getLocales(): array
    {
        return Locale::cases();
    }

    /**
     * @return array<int, string>
     */
    public function getLocaleValues(): array
    {
        return Locale::getValues();
    }

    /**
     * @return array<int, array{timezone: string, identifier: string,  offset: string, value: string}>
     */
    public function getTimezones(): array
    {
        $output = [];

        try {
            $output = $this->appCacheApcu->get('application_timezone', $this->getClosure());
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return $output;
    }

    /**
     * @throws Throwable
     *
     * @return array<int, array{timezone: string, identifier: string,  offset: string, value: string}>
     */
    public function getFormattedTimezones(): array
    {
        $output = [];

        $identifiers = DateTimeZone::listIdentifiers();

        foreach ($identifiers as $identifier) {
            $dateTimeZone = new DateTimeZone($identifier);

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
