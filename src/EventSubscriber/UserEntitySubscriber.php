<?php
declare(strict_types=1);
/**
 * /src/EventSubscriber/UserEntitySubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class UserEntitySubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserEntitySubscriber
{
    /**
     * Used encoder factory.
     *
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * Constructor
     *
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * Getter for user password encoder factory.
     *
     * @param User $user
     *
     * @return PasswordEncoderInterface
     *
     * @throws \RuntimeException
     */
    public function getEncoder(User $user): PasswordEncoderInterface
    {
        return $this->encoderFactory->getEncoder($user);
    }

    /**
     * Doctrine lifecycle event for 'prePersist' event.
     *
     * @param LifecycleEventArgs $event
     *
     * @return void
     *
     * @throws \LengthException
     * @throws \RuntimeException
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        // Get user entity object
        $user = $event->getEntity();

        // Valid user so lets change password
        if ($user instanceof User) {
            $this->changePassword($user);
        }
    }

    /**
     * Doctrine lifecycle event for 'preUpdate' event.
     *
     * @param PreUpdateEventArgs $event
     *
     * @return void
     *
     * @throws \LengthException
     * @throws \RuntimeException
     */
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        // Get user entity object
        $user = $event->getEntity();

        // Valid user so lets change password
        if ($user instanceof User) {
            $this->changePassword($user);
        }
    }

    /**
     * Method to change user password whenever it's needed.
     *
     * @param User $user
     *
     * @return void
     *
     * @throws \LengthException
     * @throws \RuntimeException
     */
    protected function changePassword(User $user): void
    {
        // Get plain password from user entity
        $plainPassword = $user->getPlainPassword();

        // Yeah, we have new plain password set, so we need to encode it
        if (!empty($plainPassword)) {
            if (\strlen($plainPassword) < 8) {
                throw new \LengthException('Too short password');
            }

            $encoder = $this->getEncoder($user);

            // Password hash callback
            $callback = function ($plainPassword) use ($encoder, $user) {
                return $encoder->encodePassword($plainPassword, $user->getSalt());
            };

            // Set new password and encode it with user encoder
            $user->setPassword($callback, $plainPassword);

            // And clean up plain password from entity
            $user->eraseCredentials();
        }
    }
}
