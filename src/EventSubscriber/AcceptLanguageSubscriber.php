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
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
     *
     * @param string $locale
     */
    public function __construct(string $locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [
                'onKernelRequest',
                100, // Note that this needs to at least `100` to get translation messages as expected
            ],
        ];
    }

    /**
     * Method to change used locale according to current request.
     *
     * @param RequestEvent $event
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
