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
     * @var UserInterface|null
     */
    private $user;

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

        $token = $tokenStorage->getToken();

        $this->user = ($token === null || $token instanceof AnonymousToken) ? null : $token->getUser();
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

        // We don't want to log OPTIONS and /healthz requests
        if ($request->getRealMethod() === 'OPTIONS' || $request->getPathInfo() === '/healthz') {
            return;
        }

        // Set needed data to logger and handle actual log
        $this->logger->setRequest($request);
        $this->logger->setResponse($event->getResponse());

        $user = $this->user;

        if ($this->user instanceof ApplicationUser) {
            /** @var ApplicationUser|UserInterface $user */
            $this->logger->setUser($user);
        } elseif ($this->user instanceof ApiKeyUser) {
            /** @var ApiKeyUser|UserInterface $user */
            $this->logger->setApiKey($user->getApiKey());
        }

        $this->logger->setMasterRequest($event->isMasterRequest());
        $this->logger->handle();
    }
}
