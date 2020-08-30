<?php
declare(strict_types = 1);
/**
 * /src/Security/Provider/ApiKeyUserProvider.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security\Provider;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\ApiKeyUser;
use App\Security\Interfaces\ApiKeyUserInterface;
use App\Security\Interfaces\ApiKeyUserProviderInterface;
use App\Security\RolesService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ApiKeyUserProvider
 *
 * @package App\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserProvider implements ApiKeyUserProviderInterface
{
    private ApiKeyRepository $apiKeyRepository;
    private RolesService $rolesService;

    /**
     * ApiKeyUserProvider constructor.
     */
    public function __construct(ApiKeyRepository $apiKeyRepository, RolesService $rolesService)
    {
        $this->apiKeyRepository = $apiKeyRepository;
        $this->rolesService = $rolesService;
    }

    public function getApiKeyForToken(string $token): ?ApiKey
    {
        return $this->apiKeyRepository->findOneBy(['token' => $token]);
    }

    public function loadUserByUsername(string $token): ApiKeyUserInterface
    {
        $apiKey = $this->getApiKeyForToken($token);

        if ($apiKey === null) {
            throw new UsernameNotFoundException('API key is not valid');
        }

        return new ApiKeyUser($apiKey, $this->rolesService->getInheritedRoles($apiKey->getRoles()));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new UnsupportedUserException('API key cannot refresh user');
    }

    public function supportsClass(string $class): bool
    {
        return $class === ApiKeyUser::class;
    }
}
