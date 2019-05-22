<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/RequestSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Entity\User as ApplicationUser;
use App\Security\ApiKeyUser;
use App\Utils\RequestLogger;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class RequestSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestLogger
     */
    private $logger;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * RequestSubscriber constructor.
     *
     * @param RequestLogger         $requestLogger
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(RequestLogger $requestLogger, TokenStorageInterface $tokenStorage)
    {
        // Store logger service
        $this->logger = $requestLogger;
        $this->tokenStorage = $tokenStorage;
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
     * @codeCoverageIgnore
     *
     * @return mixed[] The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                'onKernelResponse',
                15,
            ],
        ];
    }

    /**
     * Subscriber method to log every request / response.
     *
     * @param FilterResponseEvent $event
     *
     * @throws Exception
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // We don't want to log /healthz , /version and OPTIONS requests
        if ($path === '/healthz'
            || $path === '/version'
            || $request->getRealMethod() === 'OPTIONS'
        ) {
            return;
        }

        $this->process($event);
    }

    /**
     * Method to process current request event.
     *
     * @param FilterResponseEvent $event
     *
     * @throws Exception
     */
    private function process(FilterResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Set needed data to logger and handle actual log
        $this->logger->setRequest($request);
        $this->logger->setResponse($event->getResponse());

        $user = $this->getUser();

        if ($user instanceof ApplicationUser) {
            $this->logger->setUser($user);
        } elseif ($user instanceof ApiKeyUser) {
            $this->logger->setApiKey($user->getApiKey());
        }

        $this->logger->setMasterRequest($event->isMasterRequest());
        $this->logger->handle();
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
