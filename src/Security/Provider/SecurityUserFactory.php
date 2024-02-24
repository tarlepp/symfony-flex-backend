<?php
declare(strict_types = 1);
/**
 * /src/Security/Provider/SecurityUserFactory.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security\Provider;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\RolesService;
use App\Security\SecurityUser;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

/**
 * @package App\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @template-implements UserProviderInterface<SecurityUser>
 */
class SecurityUserFactory implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RolesService $rolesService,
    ) {
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Throwable
     */
    public function loadUserByIdentifier(string $identifier): SecurityUser
    {
        $user = $this->userRepository->loadUserByIdentifier(
            $identifier,
            (bool)preg_match('#' . Requirement::UUID_V1 . '#', $identifier)
        );

        if (!($user instanceof User)) {
            throw new UserNotFoundException(sprintf('User not found for UUID: "%s".', $identifier));
        }

        return new SecurityUser($user, $this->rolesService->getInheritedRoles($user->getRoles()));
    }

    /**
     * @throws Throwable
     */
    public function refreshUser(UserInterface $user): SecurityUser
    {
        if (!($user instanceof SecurityUser)) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        $userEntity = $this->userRepository->find($user->getUserIdentifier());

        if (!($userEntity instanceof User)) {
            throw new UserNotFoundException(sprintf('User not found for UUID: "%s".', $user->getUserIdentifier()));
        }

        return new SecurityUser($userEntity, $this->rolesService->getInheritedRoles($userEntity->getRoles()));
    }
}
