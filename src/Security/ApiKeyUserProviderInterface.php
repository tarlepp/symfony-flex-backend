<?php
declare(strict_types = 1);
/**
 * /src/Security/ApiKeyUserProviderInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Security;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;

/**
 * Interface ApiKeyUserProviderInterface
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ApiKeyUserProviderInterface
{
    /**
     * ApiKeyUserProvider constructor.
     *
     * @param ApiKeyRepository $apiKeyRepository
     * @param RolesService     $rolesService
     */
    public function __construct(ApiKeyRepository $apiKeyRepository, RolesService $rolesService);

    /**
     * Method to fetch ApiKey entity for specified token.
     *
     * @param string $token
     *
     * @return ApiKey|null
     */
    public function getApiKeyForToken(string $token): ?ApiKey;
}
