<?php
declare(strict_types = 1);
/**
 * /src/Security/Interfaces/ApiKeyUserInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security\Interfaces;

use App\Entity\ApiKey;
use App\Security\RolesService;

/**
 * Interface ApiKeyUserInterface
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface ApiKeyUserInterface
{
    
    public function __construct(ApiKey $apiKey, RolesService $rolesService);

    /**
     * Getter method for ApiKey entity
     */
    public function getApiKey(): ApiKey;
}
