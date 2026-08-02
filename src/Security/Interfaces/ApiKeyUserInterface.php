<?php
declare(strict_types = 1);

/**
 * /src/Security/Interfaces/ApiKeyUserInterface.php
 */

namespace App\Security\Interfaces;

use App\Entity\ApiKey;

interface ApiKeyUserInterface
{
    /**
     * @param array<int, string> $roles
     */
    public function __construct(ApiKey $apiKey, array $roles);
}
