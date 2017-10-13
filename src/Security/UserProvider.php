<?php
declare(strict_types=1);
/**
 * /src/Security/UserProvider.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Security;

use App\Entity\User as Entity;
use App\Repository\Traits\LoadUserByUserNameTrait;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserProvider extends EntityRepository implements UserProviderInterface, UserLoaderInterface
{
    // Traits
    use LoadUserByUserNameTrait;

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be totally reloaded (e.g. from the database),
     * or if the UserInterface object can just be merged into some internal array of users / identity map.
     *
     * @param UserInterface $user
     *
     * @return UserInterface|Entity
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        $class = \get_class($user);

        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(\sprintf('Instance of "%s" is not supported.', $class));
        }

        $output = $this->loadUserByUsername($user->getUsername());

        if ($output === null) {
            throw new NoResultException();
        }

        return $output;
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class): bool
    {
        return $this->getEntityName() === $class || \is_subclass_of($class, $this->getEntityName());
    }
}
