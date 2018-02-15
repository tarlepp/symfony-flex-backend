<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/JWTCreatedSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Entity\User;
use App\Helpers\LoggerAwareTrait;
use App\Security\RolesService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class JWTCreatedSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTCreatedSubscriber implements EventSubscriberInterface
{
    // Traits
    use LoggerAwareTrait;

    /**
     * @var RolesService
     */
    private $rolesService;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * JWTCreatedListener constructor.
     *
     * @param RolesService $rolesService
     * @param RequestStack $requestStack
     */
    public function __construct(RolesService $rolesService, RequestStack $requestStack)
    {
        $this->rolesService = $rolesService;
        $this->requestStack = $requestStack;
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
            Events::JWT_CREATED => 'onJWTCreated',
        ];
    }

    /**
     * Subscriber method to attach some custom data to current JWT payload.
     *
     * This method is called when 'lexik_jwt_authentication.on_jwt_created' event is broadcast.
     *
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        // Get current original payload
        $payload = $event->getData();

        // Update JWT expiration data
        $this->setExpiration($payload);

        // Add some extra security data to payload
        $this->setSecurityData($payload);

        // Add necessary user data to payload
        $this->setUserData($payload, $event->getUser());

        // And set new payload for JWT
        $event->setData($payload);
    }

    /**
     * Method to set/modify JWT expiration date dynamically.
     *
     * @param mixed[] $payload
     */
    private function setExpiration(array &$payload): void
    {
        // Set new exp value for JWT
        $payload['exp'] = (new \DateTime('+1 day', new \DateTimeZone('UTC')))->getTimestamp();
    }

    /**
     * Method to add some security related data to JWT payload, which are checked on JWT decode process.
     *
     * @see JWTDecodedListener
     *
     * @param mixed[] $payload
     */
    private function setSecurityData(array &$payload): void
    {
        // Get current request
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            $this->logger->alert('Request not available');

            return;
        }

        // Get bits for checksum calculation
        $bits = [
            $request->getClientIp(),
            $request->headers->get('User-Agent'),
        ];

        // Attach checksum to JWT payload
        $payload['checksum'] = \hash('sha512', \implode('|', $bits));
    }

    /**
     * Method to add all necessary user information to JWT payload.
     *
     * @param mixed[]            $payload
     * @param User|UserInterface $user
     */
    private function setUserData(array &$payload, User $user): void
    {
        // Set Roles service for User Entity
        $user->setRolesService($this->rolesService);

        // Merge login data to current payload
        $payload = \array_merge($payload, $user->getLoginData());
    }
}
