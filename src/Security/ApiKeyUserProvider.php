<?php
declare(strict_types = 1);
/**
 * /src/Security/ApiKeyUserProvider.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Security;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Class ApiKeyUserProvider
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserProvider implements ApiKeyUserProviderInterface, UserProviderInterface
{
    /**
     * @var ApiKeyRepository
     */
    private $apiKeyRepository;

    /**
     * @var RolesService
     */
    private $rolesService;

    /**
     * ApiKeyUserProvider constructor.
     *
     * @param ApiKeyRepository $apiKeyRepository
     * @param RolesService     $rolesService
     */
    public function __construct(ApiKeyRepository $apiKeyRepository, RolesService $rolesService)
    {
        $this->apiKeyRepository = $apiKeyRepository;
        $this->rolesService = $rolesService;
    }

    /**
     * Method to fetch ApiKey entity for specified token.
     *
     * @param string $token
     *
     * @return ApiKey|null
     */
    public function getApiKeyForToken(string $token): ?ApiKey
    {
        return $this->apiKeyRepository->findOneBy(['token' => $token]);
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not found. If user (API key) is found method
     * will create ApiKeyUser object for specified ApiKey entity.
     *
     * @param string $guid
     *
     * @return ApiKeyUserInterface
     *
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function loadUserByUsername($guid): ApiKeyUserInterface
    {
        $apiKey = $this->apiKeyRepository->find($guid);

        if ($apiKey === null) {
            throw new UsernameNotFoundException('API key is not valid');
        }

        return new ApiKeyUser($apiKey, $this->rolesService);
    }

    /**
     * Refreshes the user.
     *
     * It is up to the implementation to decide if the user data should be totally reloaded (e.g. from the database),
     * or if the UserInterface object can just be merged into some internal array of users / identity map.
     *
     * @param UserInterface $user
     *
     * @throws \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function refreshUser(UserInterface $user): void
    {
        throw new UnsupportedUserException('API key cannot refresh user');
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
        return ApiKeyUser::class === $class;
    }
}
