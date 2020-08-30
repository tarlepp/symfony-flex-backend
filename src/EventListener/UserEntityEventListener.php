<?php
declare(strict_types = 1);
/**
 * /src/EventListener/UserEntityEventListener.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventListener;

use App\Entity\User;
use App\Security\SecurityUser;
use Doctrine\ORM\Event\LifecycleEventArgs;
use LengthException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use function strlen;

/**
 * Class UserEntityEventListener
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserEntityEventListener
{
    private UserPasswordEncoderInterface $userPasswordEncoder;

    /**
     * Constructor of the class.
     */
    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->process($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $this->process($event);
    }

    private function process(LifecycleEventArgs $event): void
    {
        // Get user entity object
        $user = $event->getObject();

        // Valid user so lets change password
        if ($user instanceof User) {
            $this->changePassword($user);
        }
    }

    private function changePassword(User $user): void
    {
        // Get plain password from user entity
        $plainPassword = $user->getPlainPassword();

        // Yeah, we have new plain password set, so we need to encode it
        if ($plainPassword !== '') {
            if (strlen($plainPassword) < 8) {
                throw new LengthException('Too short password');
            }

            // Password hash callback
            $callback = fn (string $plainPassword): string => $this->userPasswordEncoder
                ->encodePassword(new SecurityUser($user, []), $plainPassword);

            // Set new password and encode it with user encoder
            $user->setPassword($callback, $plainPassword);

            // And clean up plain password from entity
            $user->eraseCredentials();
        }
    }
}
