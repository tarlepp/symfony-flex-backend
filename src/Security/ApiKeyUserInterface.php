<?php
declare(strict_types = 1);
/**
 * /src/Security/ApiKeyUser.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Security;

use App\Entity\ApiKey;

/**
 * Interface ApiKeyUserInterface
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ApiKeyUserInterface
{
    /**
     * ApiKeyUser constructor.
     *
     * @param ApiKey       $apiKey
     * @param RolesService $rolesService
     */
    public function __construct(ApiKey $apiKey, RolesService $rolesService);

    /**
     * Getter method for ApiKey entity
     *
     * @return ApiKey
     */
    public function getApiKey(): ApiKey;

    /**
     * Returns the roles granted to the api user.
     *
     * @return string[] The user roles
     */
    public function getRoles(): array;
}
