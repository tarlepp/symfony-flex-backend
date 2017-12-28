<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/RequestSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Entity\UserInterface as ApplicationUser;
use App\Security\ApiKeyUser;
use App\Utils\RequestLogger;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class RequestSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestSubscriber
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
     * Subscriber method to log every request / response.
     *
     * @param FilterResponseEvent $event
     *
     * @throws \Exception
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
     * @throws \Exception
     */
    private function process(FilterResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Set needed data to logger and handle actual log
        $this->logger->setRequest($request);
        $this->logger->setResponse($event->getResponse());

        $user = $this->getUser();

        if ($user instanceof ApplicationUser) {
            $this->logger->setUser(/** @scrutinizer ignore-type */$user);
        } elseif ($user instanceof ApiKeyUser) {
            $this->logger->setApiKey($user->getApiKey());
        }

        $this->logger->setMasterRequest($event->isMasterRequest());
        $this->logger->handle();
    }

    /**
     * Method to get current user from token storage.
     *
     * @return null|UserInterface|ApplicationUser|ApiKeyUser
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();

        return ($token === null || $token instanceof AnonymousToken) ? null : $token->getUser();
    }
}
