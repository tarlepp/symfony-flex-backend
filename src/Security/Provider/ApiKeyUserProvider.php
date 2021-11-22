<?php
declare(strict_types = 1);
/**
 * /src/Security/Provider/ApiKeyUserProvider.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security\Provider;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\ApiKeyUser;
use App\Security\Interfaces\ApiKeyUserProviderInterface;
use App\Security\RolesService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class ApiKeyUserProvider
 *
 * @package App\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyUserProvider implements ApiKeyUserProviderInterface, UserProviderInterface
{
    public function __construct(
        private ApiKeyRepository $apiKeyRepository,
        private RolesService $rolesService,
    ) {
    }

    public function supportsClass(string $class): bool
    {
        return $class === ApiKeyUser::class;
    }

    public function loadUserByIdentifier(string $identifier): ApiKeyUser
    {
        $apiKey = $this->getApiKeyForToken($identifier);

        if ($apiKey === null) {
            throw new UserNotFoundException('API key is not valid');
        }

        return new ApiKeyUser($apiKey, $this->rolesService->getInheritedRoles($apiKey->getRoles()));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new UnsupportedUserException('API key cannot refresh user');
    }

    public function getApiKeyForToken(string $token): ?ApiKey
    {
        return $this->apiKeyRepository->findOneBy([
            'token' => $token,
        ]);
    }

    /**
     * @reminder Remove this method when Symfony 6.0.0 is released
     *
     * @codeCoverageIgnore
     */
    public function loadUserByUsername(string $username): ApiKeyUser
    {
        return $this->loadUserByIdentifier($username);
    }
}
