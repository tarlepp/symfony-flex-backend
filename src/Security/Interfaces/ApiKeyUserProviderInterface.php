<?php
declare(strict_types = 1);
/**
 * /src/Security/Interfaces/ApiKeyUserProviderInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security\Interfaces;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\RolesService;

/**
 * @package App\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface ApiKeyUserProviderInterface
{
    public function __construct(ApiKeyRepository $apiKeyRepository, RolesService $rolesService);

    /**
     * Method to fetch ApiKey entity for specified token.
     */
    public function getApiKeyForToken(string $token): ?ApiKey;
}
