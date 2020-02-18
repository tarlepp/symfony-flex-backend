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
use App\Utils\RequestLogger;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
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
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;

    /**
     * RequestSubscriber constructor.
     *
     * @param RequestLogger         $requestLogger
     * @param UserRepository        $userRepository
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface       $logger
     */
    public function __construct(
        RequestLogger $requestLogger,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->requestLogger = $requestLogger;
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
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

        /** @var SecurityUser|ApiKeyUser|null $user */
        $user = $this->getUser();

        if ($user instanceof SecurityUser) {
            $userEntity = $this->userRepository->getReference($user->getUsername());

            if ($userEntity instanceof ApplicationUser) {
                $this->requestLogger->setUser($userEntity);
            } else {
                $this->logger->error(
                    sprintf('User not found for UUID: "%s".', $user->getUsername()),
                    self::getSubscribedEvents()
                );
            }
        } elseif ($user instanceof ApiKeyUser) {
            $this->requestLogger->setApiKey($user->getApiKey());
        }

        $this->requestLogger->setMasterRequest($event->isMasterRequest());
        $this->requestLogger->handle();
    }

    /**
     * Method to get current user from token storage.
     *
     * @return string|mixed|UserInterface|ApplicationUser|ApiKeyUser|null
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();

        return $token === null || $token instanceof AnonymousToken ? null : $token->getUser();
    }
}
