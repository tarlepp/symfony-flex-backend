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
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;
use function get_class;

/**
 * Class SecurityUserFactory
 *
 * @package App\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class SecurityUserFactory implements UserProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private RolesService $rolesService,
        private string $uuidV1Regex,
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
            (bool)preg_match('#' . $this->uuidV1Regex . '#', $identifier)
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
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $userEntity = $this->userRepository->find($user->getUserIdentifier());

        if (!($userEntity instanceof User)) {
            throw new UserNotFoundException(sprintf('User not found for UUID: "%s".', $user->getUserIdentifier()));
        }

        return new SecurityUser($userEntity, $this->rolesService->getInheritedRoles($userEntity->getRoles()));
    }

    /**
     * @todo Remove this method when Symfony 6.0.0 is released
     *
     * {@inheritDoc}
     *
     * @throws Throwable
     *
     * @codeCoverageIgnore
     */
    public function loadUserByUsername(string $username): SecurityUser
    {
        return $this->loadUserByIdentifier($username);
    }
}
