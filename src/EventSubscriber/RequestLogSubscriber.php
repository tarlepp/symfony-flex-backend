<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/RequestLogSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use App\Utils\RequestLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Throwable;
use function array_filter;
use function count;
use function in_array;
use function strpos;
use function substr;

/**
 * Class RequestLogSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestLogSubscriber implements EventSubscriberInterface
{
    private RequestLogger $requestLogger;
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private UserTypeIdentification $userService;

    /**
     * @var array<int, string>
     */
    private array $ignoredRoutes;

    /**
     * RequestSubscriber constructor.
     *
     * @param array<int, string> $ignoredRoutes
     */
    public function __construct(
        RequestLogger $requestLogger,
        UserRepository $userRepository,
        LoggerInterface $logger,
        UserTypeIdentification $userService,
        array $ignoredRoutes
    ) {
        $this->requestLogger = $requestLogger;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->userService = $userService;
        $this->ignoredRoutes = $ignoredRoutes;
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

        // We don't want to log OPTIONS requests, /_profiler* -path, ignored routes and wildcard ignored routes
        if ($request->getRealMethod() === Request::METHOD_OPTIONS
            || strpos($path, '/_profiler') !== false
            || in_array($path, $this->ignoredRoutes, true)
            || count(
                array_filter(
                    $this->ignoredRoutes,
                    static fn ($route): bool => strpos($route, '/*') !== false
                        && strpos($path, substr($route, 0, -2)) !== false
                )
            ) !== 0
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
            $userEntity = $this->userRepository->getReference($identify->getUsername());

            if ($userEntity instanceof UserEntity) {
                $this->requestLogger->setUser($userEntity);
            } else {
                $this->logger->error(
                    sprintf('User not found for UUID: "%s".', $identify->getUsername()),
                    self::getSubscribedEvents()
                );
            }
        } elseif ($identify instanceof ApiKeyUser) {
            $this->requestLogger->setApiKey($identify->getApiKey());
        }

        $this->requestLogger->setMasterRequest($event->isMasterRequest());
        $this->requestLogger->handle();
    }
}
