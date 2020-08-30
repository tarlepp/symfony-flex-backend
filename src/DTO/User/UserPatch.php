<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/User/UserPatch.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\User;

use App\DTO\Traits\PatchUserGroups;

/**
 * Class UserPatch
 *
 * @package App\DTO\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserPatch extends User
{
    use PatchUserGroups;
}
