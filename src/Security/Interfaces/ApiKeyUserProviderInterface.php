<?php
declare(strict_types = 1);

/**
 * /src/Security/Interfaces/ApiKeyUserProviderInterface.php
 */

namespace App\Security\Interfaces;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\RolesService;

interface ApiKeyUserProviderInterface
{
    public function __construct(ApiKeyRepository $apiKeyRepository, RolesService $rolesService);

    /**
     * Method to fetch ApiKey entity for specified token.
     */
    public function getApiKeyForToken(string $token): ?ApiKey;
}
