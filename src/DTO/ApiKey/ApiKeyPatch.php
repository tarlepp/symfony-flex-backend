<?php
declare(strict_types = 1);
/**
 * /src/DTO/ApiKey/ApiKeyPatch.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\ApiKey;

use App\DTO\Traits\PatchUserGroups;

/**
 * Class ApiKeyPatch
 *
 * @package App\DTO\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyPatch extends ApiKey
{
    use PatchUserGroups;
}
