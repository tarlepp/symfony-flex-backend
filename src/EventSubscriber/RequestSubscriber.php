<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/RequestSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Entity\User as ApplicationUser;
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
use App\Security\SecurityUser;
use App\Security\UserTypeIdentification;
use App\Utils\RequestLogger;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use function in_array;

/**
 * Class RequestSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestSubscriber implements EventSubscriberInterface
{
    private RequestLogger $requestLogger;
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private UserTypeIdentification $userService;

    /**
     * RequestSubscriber constructor.
     *
     * @param RequestLogger          $requestLogger
     * @param UserRepository         $userRepository
     * @param LoggerInterface        $logger
     * @param UserTypeIdentification $userService
     */
    public function __construct(
        RequestLogger $requestLogger,
        UserRepository $userRepository,
        LoggerInterface $logger,
        UserTypeIdentification $userService
    ) {
        $this->requestLogger = $requestLogger;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->userService = $userService;
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
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array<string, array<int, string|int>> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => [
                'onKernelResponse',
                15,
            ],
        ];
    }

    /**
     * Subscriber method to log every request / response.
     *
     * @param ResponseEvent $event
     *
     * @throws Exception
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        static $ignorePaths = ['', '/', '/healthz', '/version'];

        // We don't want to log ignored paths, /_profiler* -path and OPTIONS requests
        if (in_array($path, $ignorePaths, true)
            || (strpos($path, '/_profiler') !== false)
            || ($request->getRealMethod() === 'OPTIONS')
        ) {
            return;
        }

        $this->process($event);
    }

    /**
     * Method to process current request event.
     *
     * @param ResponseEvent $event
     *
     * @throws Exception
     */
    private function process(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Set needed data to logger and handle actual log
        $this->requestLogger->setRequest($request);
        $this->requestLogger->setResponse($event->getResponse());

        $identify = $this->userService->getIdentity();

        if ($identify instanceof SecurityUser) {
            $userEntity = $this->userRepository->getReference($identify->getUsername());

            if ($userEntity instanceof ApplicationUser) {
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
