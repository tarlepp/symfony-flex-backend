<?php
declare(strict_types = 1);
/**
 * /src/Security/Interfaces/ApiKeyUserInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security\Interfaces;

use App\Entity\ApiKey;

/**
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface ApiKeyUserInterface
{
    /**
     * ApiKeyUser constructor.
     *
     * @param array<int, string> $roles
     */
    public function __construct(ApiKey $apiKey, array $roles);
}
