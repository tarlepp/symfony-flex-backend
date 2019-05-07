<?php
declare(strict_types = 1);
/**
 * /src/Security/SecurityUserFactory.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;
use function get_class;

/**
 * Class SecurityUserFactory
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SecurityUserFactory implements UserProviderInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var RolesService
     */
    private $rolesService;

    /**
     * SecurityUserFactory constructor.
     *
     * @param UserRepository $userRepository
     * @param RolesService   $rolesService
     */
    public function __construct(UserRepository $userRepository, RolesService $rolesService)
    {
        $this->userRepository = $userRepository;
        $this->rolesService = $rolesService;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must return null if the user is not found.
     *
     * @param string $username The username
     *
     * @return UserInterface|null
     *
     * @throws Throwable
     */
    public function loadUserByUsername($username): ?UserInterface
    {
        $user = $this->userRepository->loadUserByUsername($username);

        if (!($user instanceof User)) {
            throw new UsernameNotFoundException(sprintf('User not found for UUID: "%s".', $username));
        }

        return (new SecurityUser($user))->setRoles($this->rolesService->getInheritedRoles($user->getRoles()));
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class): bool
    {
        return $class === SecurityUser::class;
    }

    /**
     * @inheritDoc
     *
     * @return SecurityUser
     *
     * @throws Throwable
     */
    public function refreshUser(UserInterface $user): SecurityUser
    {
        if (!($user instanceof SecurityUser)) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $userEntity = $this->userRepository->find($user->getUsername());

        if (!($userEntity instanceof User)) {
            throw new UsernameNotFoundException(sprintf('User not found for UUID: "%s".', $user->getUsername()));
        }

        return (new SecurityUser($userEntity))
            ->setRoles($this->rolesService->getInheritedRoles($userEntity->getRoles()));
    }
}
