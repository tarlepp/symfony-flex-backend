<?php
declare(strict_types = 1);
/**
 * /src/Security/UserTypeIdentification.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserTypeIdentification
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserTypeIdentification
{
    private TokenStorageInterface $tokenStorage;
    private UserRepository $userRepository;

    /**
     * UserTypeIdentification constructor.
     */
    public function __construct(TokenStorageInterface $tokenStorage, UserRepository $userRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
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

        return $user === null ? null : $this->userRepository->loadUserByUsername($user->getUsername(), true);
    }

    /**
     * Helper method to get user identity object via token storage.
     *
     * @return SecurityUser|ApiKeyUser|null
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

        if ($apiKeyUser instanceof ApiKeyUser) {
            $output = $apiKeyUser;
        }

        return $output ?? null;
    }

    /**
     * Helper method to get current logged in SecurityUser via token storage.
     */
    public function getSecurityUser(): ?SecurityUser
    {
        $securityUser = $this->getUserToken();

        if ($securityUser instanceof SecurityUser) {
            $output = $securityUser;
        }

        return $output ?? null;
    }

    /**
     * Returns a user representation. Can be a UserInterface instance, an
     * object implementing a __toString method, or the username as a regular
     * string.
     *
     * @return object|string|null
     */
    private function getUserToken()
    {
        $token = $this->tokenStorage->getToken();

        if (!($token === null || $token instanceof AnonymousToken)) {
            $output = $token->getUser();
        }

        return $output ?? null;
    }
}
