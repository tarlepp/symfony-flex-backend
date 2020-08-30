<?php
declare(strict_types = 1);
/**
 * /src/Security/Interfaces/ApiKeyUserProviderInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security\Interfaces;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\RolesService;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface ApiKeyUserProviderInterface
 *
 * @package App\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ApiKeyUserProviderInterface extends UserProviderInterface
{
    /**
     * ApiKeyUserProvider constructor.
     */
    public function __construct(ApiKeyRepository $apiKeyRepository, RolesService $rolesService);

    /**
     * Method to fetch ApiKey entity for specified token.
     */
    public function getApiKeyForToken(string $token): ?ApiKey;
}
