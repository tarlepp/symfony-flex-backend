<?php
declare(strict_types=1);
/**
 * /src/EventSubscriber/JWTCreatedSubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Entity\User;
use App\Resource\UserResource;
use App\Security\Roles;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class JWTCreatedSubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTCreatedSubscriber
{
    /**
     * @var UserResource
     */
    protected $userResource;

    /**
     * @var RoleHierarchyInterface
     */
    protected $roles;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * JWTCreatedListener constructor.
     *
     * @param   UserResource $userResource
     * @param   Roles        $roles
     * @param   RequestStack $requestStack
     */
    public function __construct(UserResource $userResource, Roles $roles, RequestStack $requestStack)
    {
        $this->userResource = $userResource;
        $this->roles = $roles;
        $this->requestStack = $requestStack;
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
     * @param array $payload
     */
    private function setExpiration(array &$payload): void
    {
        // Set new exp value for JWT
        $payload['exp'] = (new \DateTime('+1 day'))->getTimestamp();
    }

    /**
     * Method to add some security related data to JWT payload, which are checked on JWT decode process.
     *
     * @see JWTDecodedListener
     *
     * @param array $payload
     */
    private function setSecurityData(array &$payload): void
    {
        // Get current request
        $request = $this->requestStack->getCurrentRequest();

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
     * @param array              $payload
     * @param User|UserInterface $user
     */
    private function setUserData(array &$payload, User $user): void
    {
        // Set Roles service for User Entity
        $user->setRolesService($this->roles);

        // Merge login data to current payload
        $payload = \array_merge($payload, $user->getLoginData());
    }
}
