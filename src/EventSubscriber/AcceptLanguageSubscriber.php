<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/AcceptLanguageSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Enum\Language;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use function in_array;

/**
 * Class AcceptLanguageSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AcceptLanguageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%locale%')]
        private readonly string $locale,
    ) {
    }

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

        $locale = $request->headers->get('Accept-Language', $this->locale);

        // Ensure that given locale is supported, if not fallback to default.
        if (!in_array($locale, Language::getValues(), true)) {
            $locale = $this->locale;
        }

        $request->setLocale($locale);
    }
}
