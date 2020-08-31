<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/LocaleSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use function in_array;

/**
 * Class LocaleSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AcceptLanguageSubscriber implements EventSubscriberInterface
{
    // Supported locales
    public const LOCALE_EN = 'en';
    public const LOCALE_FI = 'fi';

    public const SUPPORTED_LOCALES = [
        self::LOCALE_EN,
        self::LOCALE_FI,
    ];

    private string $defaultLocale;

    /**
     * LocaleSubscriber constructor.
     */
    public function __construct(string $locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, array<int, string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [
                'onKernelRequest',
                // Note that this needs to at least `100` to get translation messages as expected
                100,
            ],
        ];
    }

    /**
     * Method to change used locale according to current request.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $locale = $request->headers->get('Accept-Language', $this->defaultLocale);

        // Ensure that given locale is supported, if not fallback to default.
        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = $this->defaultLocale;
        }

        $request->setLocale($locale);
    }
}
