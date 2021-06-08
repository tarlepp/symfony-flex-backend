<?php
declare(strict_types = 1);
/**
 * /src/Security/UserTypeIdentification.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Stringable;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserTypeIdentification
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserTypeIdentification
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Helper method to get current logged in ApiKey entity via token storage.
     */
    public function getApiKey(): ?ApiKey
    {
        $apiKeyUser = $this->getApiKeyUser();

        return $apiKeyUser === null ? null : $apiKeyUser->getApiKey();
    }

    /**
     * Helper method to get current logged in User entity via token storage.
     *
     * @throws NonUniqueResultException
     */
    public function getUser(): ?User
    {
        $user = $this->getSecurityUser();

        return $user === null ? null : $this->userRepository->loadUserByIdentifier($user->getUserIdentifier(), true);
    }

    /**
     * Helper method to get user identity object via token storage.
     */
    public function getIdentity(): ?UserInterface
    {
        return $this->getSecurityUser() ?? $this->getApiKeyUser();
    }

    /**
     * Helper method to get current logged in ApiKeyUser via token storage.
     */
    public function getApiKeyUser(): ?ApiKeyUser
    {
        $apiKeyUser = $this->getUserToken();

        return $apiKeyUser instanceof ApiKeyUser ? $apiKeyUser : null;
    }

    /**
     * Helper method to get current logged in SecurityUser via token storage.
     */
    public function getSecurityUser(): ?SecurityUser
    {
        $securityUser = $this->getUserToken();

        return $securityUser instanceof SecurityUser ? $securityUser : null;
    }

    /**
     * Returns a user representation. Can be a UserInterface instance, an
     * object implementing a __toString method, or the username as a regular
     * string.
     */
    private function getUserToken(): UserInterface | Stringable | string | null
    {
        $token = $this->tokenStorage->getToken();

        return !($token === null || $token instanceof AnonymousToken) ? $token->getUser() : null;
    }
}
