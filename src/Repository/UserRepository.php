<?php
declare(strict_types=1);
/**
 * /src/Repository/UserRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\User;
use App\Rest\Repository;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserRepository extends Repository implements UserProviderInterface, UserLoaderInterface
{
    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['username', 'firstname', 'surname', 'email'];

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not found.
     *
     * Method is override for performance reasons see link below.
     *
     * @link http://symfony2-document.readthedocs.org/en/latest/cookbook/security/entity_provider.html
     *       #managing-roles-in-the-database
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function loadUserByUsername($username): UserInterface
    {
        // Build query
        $query = $this
            ->createQueryBuilder('u')
            ->select('u, g, r')
            ->leftJoin('u.userGroups', 'g')
            ->leftJoin('g.role', 'r')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery();

        try {
            $user = $query->getSingleResult();
        } catch (NoResultException $exception) {
            \sleep(5);

            $message = \sprintf(
                'User "%s" not found',
                $username
            );

            throw new UsernameNotFoundException($message);
        }

        return $user;
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be totally reloaded (e.g. from the database),
     * or if the UserInterface object can just be merged into some internal array of users / identity map.
     *
     * @param UserInterface $user
     *
     * @return UserInterface|User
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @throws \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        $class = \get_class($user);

        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(\sprintf('Instance of "%s" is not supported.', $class));
        }

        return $this->loadUserByUsername($user->getUsername());
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
