<?php
declare(strict_types = 1);
/**
 * /src/Security/Provider/SecurityUserFactory.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security\Provider;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\RolesService;
use App\Security\SecurityUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;
use function get_class;

/**
 * Class SecurityUserFactory
 *
 * @package App\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SecurityUserFactory implements UserProviderInterface
{
    private UserRepository $userRepository;
    private RolesService $rolesService;
    private string $uuidV1Regex;

    /**
     * SecurityUserFactory constructor.
     */
    public function __construct(UserRepository $userRepository, RolesService $rolesService, string $uuidV1Regex)
    {
        $this->userRepository = $userRepository;
        $this->rolesService = $rolesService;
        $this->uuidV1Regex = $uuidV1Regex;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        $user = $this->userRepository->loadUserByUsername(
            $username,
            (bool)preg_match('#' . $this->uuidV1Regex . '#', $username)
        );

        if (!($user instanceof User)) {
            throw new UsernameNotFoundException(sprintf('User not found for UUID: "%s".', $username));
        }

        return new SecurityUser($user, $this->rolesService->getInheritedRoles($user->getRoles()));
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class;
    }

    public function refreshUser(UserInterface $user): SecurityUser
    {
        if (!($user instanceof SecurityUser)) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $userEntity = $this->userRepository->find($user->getUsername());

        if (!($userEntity instanceof User)) {
            throw new UsernameNotFoundException(sprintf('User not found for UUID: "%s".', $user->getUsername()));
        }

        return new SecurityUser($userEntity, $this->rolesService->getInheritedRoles($userEntity->getRoles()));
    }
}
