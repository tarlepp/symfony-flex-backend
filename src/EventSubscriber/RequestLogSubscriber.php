<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/RequestLogSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Security\ApiKeyUser;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use App\Utils\RequestLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Throwable;
use function array_filter;
use function array_values;
use function in_array;
use function str_contains;
use function substr;

/**
 * Class RequestLogSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @property array<int, string> $ignoredRoutes
 */
class RequestLogSubscriber implements EventSubscriberInterface
{
    /**
     * RequestLogSubscriber constructor.
     *
     * @param array<int, string> $ignoredRoutes
     */
    public function __construct(
        private RequestLogger $requestLogger,
        private UserTypeIdentification $userService,
        private array $ignoredRoutes,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, array<int, string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TerminateEvent::class => [
                'onTerminateEvent',
                15,
            ],
        ];
    }

    /**
     * Subscriber method to log every request / response.
     *
     * @throws Throwable
     */
    public function onTerminateEvent(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        $filter = static fn (string $route): bool =>
            str_contains($route, '/*') && str_contains($path, substr($route, 0, -2));

        // We don't want to log OPTIONS requests, /_profiler* -path, ignored routes and wildcard ignored routes
        if ($request->getRealMethod() === Request::METHOD_OPTIONS
            || str_contains($path, '/_profiler')
            || in_array($path, $this->ignoredRoutes, true)
            || array_values(array_filter($this->ignoredRoutes, $filter)) !== []
        ) {
            return;
        }

        $this->process($event);
    }

    /**
     * Method to process current request event.
     *
     * @throws Throwable
     */
    private function process(TerminateEvent $event): void
    {
        $request = $event->getRequest();

        // Set needed data to logger and handle actual log
        $this->requestLogger->setRequest($request);
        $this->requestLogger->setResponse($event->getResponse());

        $identify = $this->userService->getIdentity();

        if ($identify instanceof SecurityUser) {
            $this->requestLogger->setUserId($identify->getUserIdentifier());
        } elseif ($identify instanceof ApiKeyUser) {
            $this->requestLogger->setApiKeyId($identify->getApiKey()->getId());
        }

        $this->requestLogger->setMainRequest($event->isMainRequest());
        $this->requestLogger->handle();
    }
}
