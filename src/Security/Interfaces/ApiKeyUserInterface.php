<?php
declare(strict_types = 1);
/**
 * /src/Security/Interfaces/ApiKeyUser.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security\Interfaces;

use App\Entity\ApiKey;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interface ApiKeyUserInterface
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ApiKeyUserInterface extends UserInterface
{
    /**
     * ApiKeyUser constructor.
     *
     * @param ApiKey   $apiKey
     * @param string[] $roles
     */
    public function __construct(ApiKey $apiKey, array $roles);

    /**
     * Getter method for ApiKey entity
     *
     * @return ApiKey
     */
    public function getApiKey(): ApiKey;
}
