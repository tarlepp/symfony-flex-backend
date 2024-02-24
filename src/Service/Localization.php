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
 * @package App\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class Localization
{
    final public const DEFAULT_TIMEZONE = 'Europe/Helsinki';

    public function __construct(
        private readonly CacheInterface $appCacheApcu,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function getLanguages(): array
    {
        return Language::getValues();
    }

    /**
     * @return array<int, string>
     */
    public function getLocales(): array
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
            /** @var array<int, array{timezone: string, identifier: string, offset: string, value: string}> $output */
            $output = $this->appCacheApcu->get('application_timezone', $this->getClosure());
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return $output;
    }

    /**
     * @throws Throwable
     *
     * @return array<int, array{timezone: string, identifier: non-empty-string,  offset: string, value: string}>
     */
    public function getFormattedTimezones(): array
    {
        $output = [];

        /** @var array<int, non-empty-string> $identifiers */
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
            $item->expiresAfter(31_536_000);

            return $this->getFormattedTimezones();
        };
    }
}
